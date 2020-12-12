<?php

namespace Toalett\Multiprocessing\Task;

use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

class RepeatedTask extends Task
{
    public Interval $interval;

    public function __construct(Interval $interval, callable $callable, ...$arguments)
    {
        $this->interval = $interval;
        parent::__construct($callable, $arguments);
    }

    protected function generateTimer(LoopInterface $loop): TimerInterface
    {
        return $loop->addPeriodicTimer($this->interval->asFloat(), $this->createDeferredCall());
    }
}
