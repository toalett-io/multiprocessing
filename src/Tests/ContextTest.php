<?php

namespace Toalett\Multiprocessing\Tests;

use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\Timer;
use Toalett\Multiprocessing\ConcurrencyLimit;
use Toalett\Multiprocessing\Context;
use Toalett\Multiprocessing\Workers;

class ContextTest extends TestCase
{
	public function testItEmitsAnEventWhenBooted(): void
	{
		$limit = $this->createMock(ConcurrencyLimit::class);
		$loop = Factory::create();
		$context = new Context($loop, $limit);

		$loop->futureTick(fn() => $context->stop());

		$bootEventHasTakenPlace = false;
		$context->on('booted', function () use (&$bootEventHasTakenPlace) {
			$bootEventHasTakenPlace = true;
		});

		self::assertFalse($bootEventHasTakenPlace);
		$context->run();
		self::assertTrue($bootEventHasTakenPlace);
	}

	public function testItEmitsEventsWhenCongestionOccursAndIsRelieved(): void
	{
		$loop = Factory::create();
		$limit = $this->createMock(ConcurrencyLimit::class);
		$context = new Context($loop, $limit);

		$limit->method('isReachedBy')->willReturn(true); // trigger congestion

		$congestionEventHasTakenPlace = false;
		$context->on('congestion', function () use (&$congestionEventHasTakenPlace) {
			$congestionEventHasTakenPlace = true;
		});

		$congestionRelievedEventHasTakenPlace = false;
		$context->on('congestion_relieved', function () use (&$congestionRelievedEventHasTakenPlace) {
			$congestionRelievedEventHasTakenPlace = true;
		});

		self::assertFalse($congestionEventHasTakenPlace);
		self::assertFalse($congestionRelievedEventHasTakenPlace);

		$loop->futureTick(fn() => $context->stop());
		$context->submit(static fn() => null);
		$context->run();

		self::assertTrue($congestionEventHasTakenPlace);
		self::assertTrue($congestionRelievedEventHasTakenPlace);
	}

	public function testItCreatesAWorkerForASubmittedTask(): void
	{
		$limit = $this->createMock(ConcurrencyLimit::class);
		$loop = $this->createMock(LoopInterface::class);
		$context = new Context($loop, $limit);

		$limit->method('isReachedBy')->willReturn(false);
		$loop->expects(self::once())
			->method('futureTick')
			->withConsecutive([
				static fn() => null,
			]);

		$context->submit(static fn() => null);
	}

	public function testItRegistersMaintenanceTasksOnTheEventLoop(): void
	{
		$loop = $this->createMock(LoopInterface::class);
		$limit = $this->createMock(ConcurrencyLimit::class);

		$loop->expects(self::exactly(2))
			->method('addPeriodicTimer')
			->withConsecutive(
				[Context::INTERVAL_CLEANUP, static fn() => null],
				[Context::INTERVAL_GC, static fn() => null]
			)->willReturnOnConsecutiveCalls(
				new Timer(Context::INTERVAL_CLEANUP, static fn() => null),
				new Timer(Context::INTERVAL_GC, static fn() => null),
			);

		$context = new Context($loop, $limit);
		$context->run();
	}

	public function testItForwardsWorkersEventsToSelf(): void
	{
		$loop = $this->createMock(LoopInterface::class);
		$limit = $this->createMock(ConcurrencyLimit::class);
		$workers = $this->createMock(Workers::class);

		$workers->expects(self::exactly(3))
			->method('on')
			->withConsecutive(
				['worker_started', static fn() => null],
				['worker_stopped', static fn() => null],
				['no_workers_remaining', static fn() => null]
			);

		new Context($loop, $limit, $workers);
	}
}
