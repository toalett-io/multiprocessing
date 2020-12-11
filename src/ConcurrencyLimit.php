<?php

namespace Toalett\Multiprocessing;

use Toalett\Multiprocessing\Exception\InvalidArgumentException;

class ConcurrencyLimit
{
    private const VALUE_UNLIMITED = -1;
    private int $limit;

    public function __construct(int $limit)
    {
        if ($limit === 0 || $limit < self::VALUE_UNLIMITED) {
            throw new InvalidArgumentException('-1 or positive integer', $limit);
        }
        $this->limit = $limit;
    }

    public static function singleWorker(): self
    {
        return new self(1);
    }

    public static function unlimited(): self
    {
        return new self(self::VALUE_UNLIMITED);
    }

    public function isUnlimited(): bool
    {
        return $this->limit === self::VALUE_UNLIMITED;
    }

    public function isReachedBy(int $amount): bool
    {
        if ($this->isUnlimited()) {
            return false;
        }
        return $amount >= $this->limit;
    }
}
