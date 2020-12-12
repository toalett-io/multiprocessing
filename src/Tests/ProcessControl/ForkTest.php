<?php

namespace Toalett\Multiprocessing\Tests\ProcessControl;

use PHPUnit\Framework\TestCase;
use Toalett\Multiprocessing\ProcessControl\Fork;

class ForkTest extends TestCase
{
    /**
     * @param int $pid
     * @dataProvider positiveIntegerProvider
     */
    public function testItSaysItIsAParentProcessWhenAPositivePidIsProvided(int $pid): void
    {
        $fork = new Fork($pid);
        self::assertTrue($fork->isParent());
        self::assertFalse($fork->isChild());
        self::assertFalse($fork->failed());
    }

    /**
     * @param int $pid
     * @dataProvider negativeIntegerProvider
     */
    public function testItSaysItFailedWhenANegativePidIsProvided(int $pid): void
    {
        $fork = new Fork($pid);
        self::assertTrue($fork->isParent());
        self::assertFalse($fork->isChild());
        self::assertTrue($fork->failed());
    }

    public function testItSaysItIsAChildProcessWhenPidZeroIsProvided(): void
    {
        $fork = new Fork(0);
        self::assertFalse($fork->isParent());
        self::assertTrue($fork->isChild());
        self::assertFalse($fork->failed());
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
