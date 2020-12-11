<?php

namespace Toalett\Multiprocessing;

use Countable;
use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use Throwable;
use Toalett\Multiprocessing\Exception\ProcessControlException;

class Workers implements Countable, EventEmitterInterface
{
	use EventEmitterTrait;

	/** @var int[] */
	private array $workers = [];

	public function count(): int
	{
		return count($this->workers);
	}

	public function empty(): bool
	{
		return count($this->workers) === 0;
	}

	public function createWorkerFor(callable $task, array $args): void
	{
		$pid = $this->forkWorker($task, $args);
		$this->workers[$pid] = $pid;
		$this->emit('worker_started', [$pid]);
	}

	public function cleanup(): void
	{
		while (true === $this->wait(WNOHANG)) ;
	}

	public function awaitCongestionRelief(): void
	{
		$this->wait();
	}

	private function remove(int $pid): void
	{
		unset($this->workers[$pid]);
		$this->emit('worker_stopped', [$pid]);
	}

	private function forkWorker(callable $task, array $args): int
	{
		$pid = pcntl_fork();
		if ($pid === -1) {
			throw ProcessControlException::forkFailed();
		}

		if ($pid === 0) {
			try {
				call_user_func_array($task, $args);
			} catch (Throwable $t) {
				fwrite(STDERR, $t->getMessage());
				exit(1);
			}
			exit(0);
		}

		return $pid;
	}

	/**
	 * @param int $options
	 * @return bool Whether a process was caught
	 */
	private function wait(int $options = 0): bool
	{
		$pid = pcntl_wait($status, $options);
		if ($pid > 0) {
			$this->remove($pid);
			return true;
		}
		// We ignore errors ($pid < 0). This method is called periodically, even if there is
		// no child available. pcntl_wait() will return -1. This is expected behavior.
		return false;
	}

	public function stop(): void
	{
		while (true === $this->wait());
	}
}
