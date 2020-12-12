<?php

namespace Toalett\Multiprocessing;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use React\EventLoop\LoopInterface;
use Toalett\Multiprocessing\Task\Interval;
use Toalett\Multiprocessing\Task\RepeatedTask;
use Toalett\Multiprocessing\Task\Tasks;

class Context implements EventEmitterInterface
{
	public const INTERVAL_GC = 120;
	public const INTERVAL_CLEANUP = 5;
	use EventEmitterTrait;

	private LoopInterface $eventLoop;
	private ConcurrencyLimit $limit;
	private Workers $workers;
	private Tasks $maintenanceTasks;

	public function __construct(
		LoopInterface $eventLoop,
		ConcurrencyLimit $limit,
		?Workers $workers = null,
		?Interval $cleanupInterval = null,
		?Interval $garbageCollectionInterval = null
	)
	{
		$this->eventLoop = $eventLoop;
		$this->limit = $limit;
		$this->workers = $workers ?? new Workers();
		$this->setupWorkerEventForwarding();
		$this->setupMaintenanceTasks($cleanupInterval, $garbageCollectionInterval);
	}

	public function run(): void
	{
		$this->eventLoop->futureTick(fn() => $this->emit('booted'));
		$this->eventLoop->futureTick(fn() => gc_enable());
		$this->maintenanceTasks->enable($this->eventLoop);
		$this->eventLoop->run();
	}

	public function submit(callable $task, ...$args): void
	{
		$this->eventLoop->futureTick(function () use ($task, $args) {
			if ($this->limit->isReachedBy(count($this->workers))) {
				$this->emit('congestion');
				$this->workers->awaitCongestionRelief();
				$this->emit('congestion_relieved');
			}
			$this->workers->createWorkerFor($task, $args);
		});
	}

	public function stop(): void
	{
		$this->maintenanceTasks->cancel();
		$this->workers->stop();
		$this->emit('stopped');
	}

	private function setupWorkerEventForwarding(): void
	{
		$this->workers->on('worker_started', fn(int $pid) => $this->emit('worker_started', [$pid]));
		$this->workers->on('worker_stopped', fn(int $pid) => $this->emit('worker_stopped', [$pid]));
		$this->workers->on('no_workers_remaining', fn() => $this->emit('no_workers_remaining'));
	}

	private function setupMaintenanceTasks(?Interval $cleanupInterval, ?Interval $garbageCollectionInterval): void
	{
		$this->maintenanceTasks = new Tasks(
			new RepeatedTask(
				$cleanupInterval ?? Interval::seconds(self::INTERVAL_CLEANUP),
				fn() => $this->workers->cleanup()
			),
			new RepeatedTask(
				$garbageCollectionInterval ?? Interval::seconds(self::INTERVAL_GC),
				fn() => gc_collect_cycles()
			)
		);
	}
}
