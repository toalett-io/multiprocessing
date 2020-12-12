<?php

namespace Toalett\Multiprocessing\Tests\ProcessControl;

use PHPUnit\Framework\TestCase;
use Toalett\Multiprocessing\ProcessControl\Wait;

class WaitTest extends TestCase
{
    /**
     * @param int $pid
     * @dataProvider positiveIntegerProvider
     */
    public function testItSaysAChildStoppedWhenAPositivePidIsProvided(int $pid): void
    {
        $wait = new Wait($pid, 0);
        self::assertTrue($wait->childStopped());
        self::assertFalse($wait->failed());
    }

    /**
     * @param int $pid
     * @dataProvider negativeIntegerProvider
     */
    public function testItSaysItFailedWhenANegativePidIsProvided(int $pid): void
    {
        $wait = new Wait($pid, 0);
        self::assertFalse($wait->childStopped());
        self::assertTrue($wait->failed());
    }

    public function positiveIntegerProvider(): array
    {
        return [
            [1],
            [10],
            [1000],
            [PHP_INT_MAX],
        ];
    }

    public function negativeIntegerProvider(): array
    {
        return [
            [-1],
            [-10],
            [-1000],
            [PHP_INT_MIN],
        ];
    }
}
