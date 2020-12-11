<?php

namespace Toalett\Multiprocessing\Exception;

use RuntimeException;

class ProcessControlException extends RuntimeException
{
    private function __construct(string $functionName)
    {
        parent::__construct(sprintf('call to \'%s\' failed', $functionName));
    }

    public static function forkFailed(): self
    {
        return new self('pcntl_fork');
    }

    public static function waitFailed(): self
    {
        return new self('pcntl_wait');
    }
}
