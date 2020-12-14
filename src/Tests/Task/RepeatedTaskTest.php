<?php

namespace Toalett\Multiprocessing\Tests\Task;

use Generator;
use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\Timer;
use Toalett\Multiprocessing\Task\Interval;
use Toalett\Multiprocessing\Task\RepeatedTask;
use Toalett\Multiprocessing\Tests\Tools\CallableProvider;

class RepeatedTaskTest extends TestCase
{
    use CallableProvider;

    /**
     * @param $interval
     * @dataProvider dataProvider
     */
    public function testItRegistersWithTheProvidedInterval(Interval $interval): void
    {
        $loop = $this->createMock(LoopInterface::class);
        $loop->expects(self::once())
            ->method('addPeriodicTimer')
            ->with($interval->asFloat(), self::emptyCallable())
            ->willReturn(new Timer($interval->asFloat(), self::emptyCallable(), true));

        $task = new RepeatedTask($interval, self::emptyCallable());
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
