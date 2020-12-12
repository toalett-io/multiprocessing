<?php

namespace Toalett\Multiprocessing\Task;

use React\EventLoop\LoopInterface;

class Tasks
{
	/** @var Task[] */
	private array $tasks;
	private ?LoopInterface $loop = null;

	public function __construct(Task ...$tasks)
	{
		$this->tasks = $tasks;
	}

	public function enable(LoopInterface $loop): void
	{
		if (is_null($this->loop)) {
			$this->loop = $loop;
			foreach ($this->tasks as $task) {
				$task->enable($this->loop);
			}
		}
	}

	public function cancel(): void
	{
		if (!is_null($this->loop)) {
			foreach ($this->tasks as $task) {
				$task->cancel($this->loop);
			}
			$this->loop = null;
		}
	}
}
