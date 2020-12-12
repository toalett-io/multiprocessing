<?php

namespace Toalett\Multiprocessing\Tests\Task;

use PHPUnit\Framework\MockObject\MockObject;
use React\EventLoop\LoopInterface;
use Toalett\Multiprocessing\Task\Task;
use Toalett\Multiprocessing\Task\Tasks;
use PHPUnit\Framework\TestCase;
use Toalett\Multiprocessing\Tests\Tools\PropertyInspector;

class TasksTest extends TestCase
{
	use PropertyInspector;

	public function testItAcceptsZeroTasks(): void
	{
		$this->expectNotToPerformAssertions();
		new Tasks();
	}

	public function testItAcceptsMultipleTasks(): void
	{
		$this->expectNotToPerformAssertions();
		new Tasks(
			$this->createMock(Task::class),
			$this->createMock(Task::class)
		);
	}

	public function testItDoesNotReEnableWhenEnabled(): void
	{
		$loop = $this->createMock(LoopInterface::class);
		$task = $this->createMock(Task::class);
		$tasks = new Tasks($task);

		$task->expects(self::once())
			->method('enable')
			->with($loop);

		$tasks->enable($loop);
		$tasks->enable($loop);
	}

	public function testItEnablesAllTasksWhenEnableCalled(): void
	{
		$loop = $this->createMock(LoopInterface::class);
		$task1 = $this->createMock(Task::class);
		$task2 = $this->createMock(Task::class);
		$task3 = $this->createMock(Task::class);

		foreach([$task1, $task2, $task3] as $task) {
			/** @var MockObject|Task $task */
			$task->expects(self::once())->method('enable')->with($loop);
		}

		(new Tasks($task1, $task2, $task3))->enable($loop);
	}

	public function testItCancelsAllTasksWhenCancelCalled(): void
	{
		$loop = $this->createMock(LoopInterface::class);
		$task1 = $this->createMock(Task::class);
		$task2 = $this->createMock(Task::class);
		$task3 = $this->createMock(Task::class);

		foreach([$task1, $task2, $task3] as $task) {
			/** @var MockObject|Task $task */
			$task->expects(self::once())->method('cancel')->with($loop);
		}

		$tasks = new Tasks($task1, $task2, $task3);
		$this->setProperty($tasks, 'loop', $loop);
		$tasks->cancel();
	}

	public function testItDoesNotCancelTasksWhenTheyAreNotEnabled(): void
	{
		$task1 = $this->createMock(Task::class);
		$task2 = $this->createMock(Task::class);
		$task3 = $this->createMock(Task::class);

		foreach([$task1, $task2, $task3] as $task) {
			/** @var MockObject|Task $task */
			$task->expects(self::never())->method('cancel');
		}

		$tasks = new Tasks($task1, $task2, $task3);
		$tasks->cancel();
	}
}
