<?php

namespace Toalett\Multiprocessing\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Toalett\Multiprocessing\ProcessControl\Fork;
use Toalett\Multiprocessing\ProcessControl\ProcessControl;
use Toalett\Multiprocessing\ProcessControl\Wait;
use Toalett\Multiprocessing\Workers;

class WorkersTest extends TestCase
{
	public function testItSaysItIsEmptyWhenNoWorkers(): void
	{
		$processControl = $this->createMock(ProcessControl::class);
		$workers = new Workers($processControl);
		self::assertEmpty($workers);
	}

	public function testItSaysItHasOneWorkerWhenTaskExecutes(): void
	{
		$workers = new Workers();

		$workers->createWorkerFor(fn() => exit(0), []);
		self::assertCount(1, $workers);
	}

	public function testItGivesTheAmountOfActiveWorkersOnCount(): void
	{
		$workers = new Workers();

		$workers->createWorkerFor(fn() => exit(0), []);
		$workers->createWorkerFor(fn() => exit(0), []);
		self::assertCount(2, $workers);

		$workers->createWorkerFor(fn() => exit(0), []);
		self::assertCount(3, $workers);

		$workers->stop();
		self::assertEmpty($workers);
	}

	public function testItEmitsAnEventWhenAWorkerIsStarted(): void
	{
		$workers = new Workers();

		$workerStartedEventHasTakenPlace = false;
		$workers->on('worker_started', function () use (&$workerStartedEventHasTakenPlace) {
			$workerStartedEventHasTakenPlace = true;
		});

		self::assertFalse($workerStartedEventHasTakenPlace);
		$workers->createWorkerFor(fn() => exit(0), []);
		self::assertTrue($workerStartedEventHasTakenPlace);
	}

	public function testItEmitsAnEventWhenAWorkerIsRemoved(): void
	{
		$workers = new Workers();
		$reflector = new ReflectionObject($workers);
		$method = $reflector->getMethod('remove');
		$method->setAccessible(true);

		$workerStoppedEventHasTakenPlace = false;
		$workers->on('worker_stopped', function () use (&$workerStoppedEventHasTakenPlace) {
			$workerStoppedEventHasTakenPlace = true;
		});

		self::assertFalse($workerStoppedEventHasTakenPlace);
		$method->invoke($workers, 0);
		self::assertTrue($workerStoppedEventHasTakenPlace);
	}

	public function testItEmitsAnEventWhenNoWorkersRemain(): void
	{
		$workers = new Workers();

		$noWorkersRemainingEventHasTakenPlace = false;
		$workers->on('no_workers_remaining', function () use (&$noWorkersRemainingEventHasTakenPlace) {
			$noWorkersRemainingEventHasTakenPlace = true;
		});

		self::assertFalse($noWorkersRemainingEventHasTakenPlace);
		$workers->cleanup();
		self::assertTrue($noWorkersRemainingEventHasTakenPlace);
	}

	public function testItCallsForkOnProcessControlWhenAskedToCreateAWorker(): void
	{
		$processControl = $this->createMock(ProcessControl::class);
		$processControl->expects(self::once())
			->method('fork')
			->willReturn(new Fork(1));

		$workers = new Workers($processControl);
		$workers->createWorkerFor(fn() => []);
	}

	public function testItCallsNonBlockingWaitOnProcessControlWhenPerformingCleanup(): void
	{
		$processControl = $this->createMock(ProcessControl::class);
		$processControl->expects(self::once())
			->method('wait')
			->with(Wait::NO_HANG)
			->willReturn(new Wait(0));

		$workers = new Workers($processControl);
		$workers->cleanup();
	}

	public function testItCallsBlockingWaitOnProcessControlWhenAwaitingCongestionRelief(): void
	{
		$processControl = $this->createMock(ProcessControl::class);
		$processControl->expects(self::once())
			->method('wait')
			->with(/* no arguments */)
			->willReturn(new Wait(1));

		$workers = new Workers($processControl);
		$workers->awaitCongestionRelief();
	}
}
