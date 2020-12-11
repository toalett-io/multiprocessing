<?php

namespace Toalett\Multiprocessing;

use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;

class ContextBuilder
{
    private ?LoopInterface $loop = null;
    private ?ConcurrencyLimit $limit = null;

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

    public function withLimit(ConcurrencyLimit $limit): self
    {
        $instance = clone $this;
        $instance->limit = $limit;
        return $instance;
    }

    public function build(): Context
    {
        return new Context(
            $this->loop ?? Factory::create(),
            $this->limit ?? ConcurrencyLimit::unlimited()
        );
    }
}
