<?php

namespace Toalett\Multiprocessing\Exception;

class InvalidArgumentException extends \InvalidArgumentException
{
    public function __construct(string $expected, string $actual)
    {
        parent::__construct(sprintf('Expected %s, got \'%s\'', $expected, $actual));
    }
}
