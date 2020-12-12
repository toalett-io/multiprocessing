<?php

namespace Toalett\Multiprocessing;

use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use Toalett\Multiprocessing\Task\Interval;

class ContextBuilder
{
	private ?LoopInterface $loop = null;
	private ?ConcurrencyLimit $limit = null;
	private ?Workers $workers = null;
	private ?Interval $garbageCollectionInterval = null;
	private ?Interval $cleanupInterval = null;

	public static function create(): self
	{
		return new self();
	}

	public function withEventLoop(LoopInterface $loop): self
	{
		$instance = clone $this;
		$instance->loop = $loop;
		return $instance;
	}

	public function withLimit(ConcurrencyLimit $limit): self
	{
		$instance = clone $this;
		$instance->limit = $limit;
		return $instance;
	}

	public function withWorkers(Workers $workers): self
	{
		$instance = clone $this;
		$instance->workers = $workers;
		return $instance;
	}

	public function withGarbageCollectionInterval(Interval $interval): self
	{
		$instance = clone $this;
		$instance->garbageCollectionInterval = $interval;
		return $instance;
	}

	public function withCleanupInterval(Interval $interval): self
	{
		$instance = clone $this;
		$instance->cleanupInterval = $interval;
		return $instance;
	}

	public function build(): Context
	{
		return new Context(
			$this->loop ?? Factory::create(),
			$this->limit ?? ConcurrencyLimit::unlimited(),
			$this->workers,
			$this->cleanupInterval,
			$this->garbageCollectionInterval
		);
	}
}
