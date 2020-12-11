<?php

namespace Toalett\Multiprocessing;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use React\EventLoop\LoopInterface;

class Context implements EventEmitterInterface
{
	public const GC_INTERVAL = 120;
	public const CLEANUP_INTERVAL = 5;
	use EventEmitterTrait;

	private LoopInterface $eventLoop;
	private ConcurrencyLimit $limit;
	private Workers $workers;

	public function __construct(LoopInterface $eventLoop, ConcurrencyLimit $limit, ?Workers $workers = null)
	{
		$this->eventLoop = $eventLoop;
		$this->limit = $limit;
		$this->workers = $workers ?? new Workers();

		$this->eventLoop->futureTick(fn() => $this->emit('booted'));
		$this->eventLoop->futureTick(fn() => gc_enable());
		$this->eventLoop->addPeriodicTimer(self::CLEANUP_INTERVAL, fn() => $this->workers->cleanup());
		$this->eventLoop->addPeriodicTimer(self::GC_INTERVAL, fn() => gc_collect_cycles());

		$this->workers->on('worker_started', fn(int $pid) => $this->emit('worker_started', [$pid]));
		$this->workers->on('worker_stopped', fn(int $pid) => $this->emit('worker_stopped', [$pid]));
		$this->workers->on('worker_stopped', fn() => $this->emitIf($this->workers->empty(), 'no_workers_remaining'));
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

	public function run(): void
	{
		$this->eventLoop->run();
	}

	public function stop(): void
	{
		$this->eventLoop->futureTick(function()  {
			$this->eventLoop->stop();
			$this->workers->stop();
			$this->emit('stopped');
		});
	}

	public function emitIf(bool $condition, string $event, ...$args): void
	{
		if ($condition) {
			$this->emit($event, $args);
		}
	}
}
