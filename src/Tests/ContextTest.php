<?php

namespace Toalett\Multiprocessing\Tests;

use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
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
		$loop->futureTick(fn() => $context->stop());

		$congestionEventHasTakenPlace = false;
		$congestionRelievedEventHasTakenPlace = false;
		$context->on('congestion', function () use (&$congestionEventHasTakenPlace) {
			$congestionEventHasTakenPlace = true;
		});
		$context->on('congestion_relieved', function () use (&$congestionRelievedEventHasTakenPlace) {
			$congestionRelievedEventHasTakenPlace = true;
		});

		self::assertFalse($congestionEventHasTakenPlace);
		self::assertFalse($congestionRelievedEventHasTakenPlace);
		$context->submit(fn() => []);
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
		$loop->expects(self::once())->method('futureTick')->withConsecutive(
			[fn() => []],
		);

		$context->submit(fn() => []);
	}

	public function testItRegistersMaintenanceCallbacksOnTheEventLoop(): void
	{
		$loop = $this->createMock(LoopInterface::class);
		$limit = $this->createMock(ConcurrencyLimit::class);

		$loop->expects(self::exactly(2))->method('addPeriodicTimer')->withConsecutive(
			[Context::CLEANUP_INTERVAL, fn() => []],
			[Context::GC_INTERVAL, fn() => []]
		);

		new Context($loop, $limit);
	}

	public function testItProxiesWorkersEventsToSelf(): void
	{
		$loop = $this->createMock(LoopInterface::class);
		$limit = $this->createMock(ConcurrencyLimit::class);
		$workers = $this->createMock(Workers::class);

		$workers->expects(self::atLeast(2))->method('on')->withConsecutive(
			['worker_started', fn() => []],
			['worker_stopped', fn() => []]
		);

		new Context($loop, $limit, $workers);
	}
}
