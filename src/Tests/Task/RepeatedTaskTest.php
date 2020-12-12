<?php

namespace Toalett\Multiprocessing\Tests\Task;

use Generator;
use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\Timer;
use Toalett\Multiprocessing\Task\Interval;
use Toalett\Multiprocessing\Task\RepeatedTask;

class RepeatedTaskTest extends TestCase
{
	/**
	 * @param $interval
	 * @dataProvider dataProvider
	 */
	public function testItRegistersWithTheProvidedInterval(Interval $interval): void
	{
		$loop = $this->createMock(LoopInterface::class);
		$loop->expects(self::once())
			->method('addPeriodicTimer')
			->with($interval->asFloat(), static fn() => null)
			->willReturn(new Timer($interval->asFloat(), static fn() => null, true));

		$task = new RepeatedTask($interval, static fn() => null);
		$task->enable($loop);
	}

	public function dataProvider(): Generator
	{
		yield "3 seconds" => [Interval::seconds(3)];
		yield "5 minutes" => [Interval::minutes(5)];
		yield "half an hour" => [Interval::hours(0.5)];
		yield "a day" => [Interval::hours(24)];
	}
}
