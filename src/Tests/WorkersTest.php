<?php

namespace Toalett\Multiprocessing\Tests;

use ReflectionObject;
use Toalett\Multiprocessing\Workers;
use PHPUnit\Framework\TestCase;

class WorkersTest extends TestCase
{
	public function testItEmitsAnEventWhenAWorkerIsStarted(): void
	{
		$workers = new Workers();

		$workerStartedEventHasTakenPlace = false;
		$workers->on('worker_started', function() use (&$workerStartedEventHasTakenPlace) {
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
		$workers->on('worker_stopped', function() use (&$workerStoppedEventHasTakenPlace) {
			$workerStoppedEventHasTakenPlace = true;
		});

		self::assertFalse($workerStoppedEventHasTakenPlace);
		$method->invoke($workers, 0);
		self::assertTrue($workerStoppedEventHasTakenPlace);
	}
}
