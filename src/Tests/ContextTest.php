<?php

namespace Toalett\Multiprocessing\Tests;

use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\Timer;
use Toalett\Multiprocessing\Concurrency;
use Toalett\Multiprocessing\Context;
use Toalett\Multiprocessing\Tests\Tools\CallableProvider;
use Toalett\Multiprocessing\Workers;

class ContextTest extends TestCase
{
    use CallableProvider;

    public function testItEmitsAnEventWhenBooted(): void
    {
        $concurrency = $this->createMock(Concurrency::class);
        $loop = Factory::create();
        $context = new Context($loop, $concurrency);

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
        $concurrency = $this->createMock(Concurrency::class);
        $context = new Context($loop, $concurrency);

        $concurrency->method('isReachedBy')->willReturn(true); // trigger congestion

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
        $context->submit(self::emptyCallable());
        $context->run();

        self::assertTrue($congestionEventHasTakenPlace);
        self::assertTrue($congestionRelievedEventHasTakenPlace);
    }

    public function testItCreatesAWorkerForASubmittedTask(): void
    {
        $concurrency = $this->createMock(Concurrency::class);
        $loop = $this->createMock(LoopInterface::class);
        $context = new Context($loop, $concurrency);

        $concurrency->method('isReachedBy')->willReturn(false);
        $loop->expects(self::once())
            ->method('futureTick')
            ->with(self::emptyCallable());

        $context->submit(self::emptyCallable());
    }

    public function testItRegistersMaintenanceTasksOnTheEventLoop(): void
    {
        $loop = $this->createMock(LoopInterface::class);
        $concurrency = $this->createMock(Concurrency::class);

        $loop->expects(self::exactly(2))
            ->method('addPeriodicTimer')
            ->withConsecutive(
                [Context::INTERVAL_CLEANUP, self::emptyCallable()],
                [Context::INTERVAL_GC, self::emptyCallable()]
            )->willReturnOnConsecutiveCalls(
                new Timer(Context::INTERVAL_CLEANUP, self::emptyCallable()),
                new Timer(Context::INTERVAL_GC, self::emptyCallable()),
            );

        $context = new Context($loop, $concurrency);
        $context->run();
    }

    public function testItForwardsWorkersEventsToSelf(): void
    {
        $loop = $this->createMock(LoopInterface::class);
        $concurrency = $this->createMock(Concurrency::class);
        $workers = $this->createMock(Workers::class);

        $workers->expects(self::exactly(3))
            ->method('on')
            ->withConsecutive(
                ['worker_started', self::emptyCallable()],
                ['worker_stopped', self::emptyCallable()],
                ['no_workers_remaining', self::emptyCallable()]
            );

        new Context($loop, $concurrency, $workers);
    }
}
