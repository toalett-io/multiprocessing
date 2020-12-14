<?php

namespace Toalett\Multiprocessing\Task;

use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

abstract class Task
{
    public $callable;
    public array $arguments;
    protected ?TimerInterface $timer = null;

    public function __construct(callable $callable, array $arguments)
    {
        $this->callable = $callable;
        $this->arguments = $arguments;
    }

    abstract protected function generateTimer(LoopInterface $loop): TimerInterface;

    protected function createDeferredCall(): callable
    {
        return fn() => call_user_func_array(
            $this->callable,
            $this->arguments
        );
    }

    public function enable(LoopInterface $loop): void
    {
        if (!$this->isBound()) {
            $this->timer = $this->generateTimer($loop);
        }
    }

    public function isBound(): bool
    {
        return !is_null($this->timer);
    }

    public function cancel(LoopInterface $loop): void
    {
        if ($this->isBound()) {
            $loop->cancelTimer($this->timer);
        }
    }
}
