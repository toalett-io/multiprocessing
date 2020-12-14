<?php

namespace Toalett\Multiprocessing;

use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use Toalett\Multiprocessing\Task\Interval;

class ContextBuilder
{
    private ?LoopInterface $loop = null;
    private ?Concurrency $concurrency = null;
    private ?Workers $workers = null;
    private ?Interval $cleanupInterval = null;

    public static function create(): self
    {
        return new self();
    }

    public function withEventLoop(LoopInterface $loop): self
    {
        $instance = clone $this;
        $instance->loop = $loop;
        return $instance;
    }

    public function withConcurrency(Concurrency $concurrency): self
    {
        $instance = clone $this;
        $instance->concurrency = $concurrency;
        return $instance;
    }

    public function withCleanupInterval(Interval $interval): self
    {
        $instance = clone $this;
        $instance->cleanupInterval = $interval;
        return $instance;
    }

    public function build(): Context
    {
        return new Context(
            $this->loop ?? Factory::create(),
            $this->concurrency ?? Concurrency::unlimited(),
            $this->workers,
            $this->cleanupInterval
        );
    }
}
