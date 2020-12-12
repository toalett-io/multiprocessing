<?php

namespace Toalett\Multiprocessing\Tests;

use PHPUnit\Framework\TestCase;
use Toalett\Multiprocessing\Concurrency;
use Toalett\Multiprocessing\Exception\InvalidArgumentException;
use Toalett\Multiprocessing\Tests\Tools\PropertyInspector;

class ConcurrencyTest extends TestCase
{
    use PropertyInspector;

    public function testItAcceptsNegativeOneAsUnlimited(): void
    {
        $concurrency = Concurrency::atMost(-1);

        self::assertTrue($concurrency->isUnlimited());
    }

    public function testItDoesNotAcceptZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected -1 or positive integer, got \'0\'');

        Concurrency::atMost(0);
    }

    /**
     * @param int $negativeNumber
     * @dataProvider negativeValueProvider
     */
    public function testItDoesNotAllowAnyOtherNegativeValue(int $negativeNumber): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Expected -1 or positive integer, got \'%s\'', $negativeNumber));

        Concurrency::atMost($negativeNumber);
    }

    public function testTheLimitMayBeUnlimited(): void
    {
        $concurrency = Concurrency::unlimited();
        self::assertTrue($concurrency->isUnlimited());
    }

    public function testTheLimitMayBeASingleWorker(): void
    {
        $concurrency = Concurrency::singleWorker();

        self::assertFalse($concurrency->isUnlimited());
        self::assertEquals(1, $this->getProperty($concurrency, 'limit'));
    }

    public function testAnUnlimitedLimitCanNeverBeReached(): void
    {
        $concurrency = Concurrency::unlimited();

        self::assertFalse($concurrency->isReachedBy(PHP_INT_MIN));
        self::assertFalse($concurrency->isReachedBy(0));
        self::assertFalse($concurrency->isReachedBy(PHP_INT_MAX));
    }

    public function testABoundLimitCanBeReached(): void
    {
        $three = Concurrency::atMost(3);
        $seven = Concurrency::atMost(7);

        self::assertTrue($three->isReachedBy(3));
        self::assertFalse($three->isReachedBy(2));
        self::assertFalse($three->isReachedBy(1));

        self::assertTrue($seven->isReachedBy(7));
        self::assertTrue($seven->isReachedBy(120));
        self::assertFalse($seven->isReachedBy(-2));
    }

    public function negativeValueProvider(): array
    {
        return [
            '-2'          => [-2],
            '-3'          => [-3],
            '-10000'      => [-10000],
            'PHP_INT_MIN' => [PHP_INT_MIN],
        ];
    }
}
