<?php

namespace Toalett\Multiprocessing\Tests;

use PHPUnit\Framework\TestCase;
use Toalett\Multiprocessing\ConcurrencyLimit;
use Toalett\Multiprocessing\Exception\InvalidArgumentException;
use Toalett\Multiprocessing\Tests\Tools\PropertyInspector;

class ConcurrencyLimitTest extends TestCase
{
	use PropertyInspector;

	public function testItDoesNotAcceptZero(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Expected -1 or positive integer, got \'0\'');

		ConcurrencyLimit::atMost(0);
	}

	public function testItAcceptsNegativeOneAsUnlimited(): void
	{
		$limit = ConcurrencyLimit::atMost(-1);

		self::assertTrue($limit->isUnlimited());
	}

	/**
	 * @param int $negativeNumber
	 * @dataProvider negativeValueProvider
	 */
	public function testItDoesNotAllowAnyOtherNegativeValue(int $negativeNumber): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage(sprintf('Expected -1 or positive integer, got \'%s\'', $negativeNumber));

		ConcurrencyLimit::atMost($negativeNumber);
	}

	public function testTheLimitMayBeUnlimited(): void
	{
		$limit = ConcurrencyLimit::unlimited();

		self::assertTrue($limit->isUnlimited());
	}

	public function testTheLimitMayBeASingleWorker(): void
	{
		$limit = ConcurrencyLimit::singleWorker();

		self::assertFalse($limit->isUnlimited());
		self::assertEquals(1, $this->getProperty($limit, 'limit'));
	}

	public function testAnUnlimitedLimitCanNeverBeReached(): void
	{
		$limit = ConcurrencyLimit::unlimited();

		self::assertFalse($limit->isReachedBy(PHP_INT_MIN));
		self::assertFalse($limit->isReachedBy(0));
		self::assertFalse($limit->isReachedBy(PHP_INT_MAX));
	}

	public function testABoundLimitCanBeReached(): void
	{
		$three = ConcurrencyLimit::atMost(3);
		$seven = ConcurrencyLimit::atMost(7);

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
