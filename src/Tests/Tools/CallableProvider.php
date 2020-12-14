<?php

namespace Toalett\Multiprocessing\Tests\Tools;

trait CallableProvider
{
    protected static function emptyCallable(): callable
    {
        static $fn = null;
        $fn ??= static fn() => null;
        return $fn;
    }
}
