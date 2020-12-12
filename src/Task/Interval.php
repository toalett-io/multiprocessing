<?php

namespace Toalett\Multiprocessing\Task;

use Toalett\Multiprocessing\Exception\InvalidArgumentException;

class Interval
{
    private float $seconds;

    private function __construct(float $seconds)
    {
        if ($seconds <= 0) {
            throw new InvalidArgumentException('positive float', $seconds);
        }
        $this->seconds = $seconds;
    }

    public static function seconds(float $seconds): self
    {
        return new self($seconds);
    }

    public static function minutes(float $minutes): self
    {
        return new self(60.0 * $minutes);
    }

    public static function hours(float $hours): self
    {
        return new self(3600.0 * $hours);
    }

    public function asFloat(): float
    {
        return $this->seconds;
    }

    public function asInt(): int
    {
        return (int)$this->seconds;
    }
}
