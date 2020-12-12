<?php

namespace Toalett\Multiprocessing\Tests\Task;

use Generator;
use PHPUnit\Framework\TestCase;
use Toalett\Multiprocessing\Exception\InvalidArgumentException;
use Toalett\Multiprocessing\Task\Interval;

class IntervalTest extends TestCase
{
	/**
	 * @param $method
	 * @param $val
	 * @param $calculatedVal
	 * @dataProvider zeroAndDownProvider
	 */
	public function testItDoesNotAllowLessThanZeroOrZero($method, $val, $calculatedVal): void
	{
		$this->setName(sprintf('It does not allow %d for %s', $val, $method));
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage(sprintf('Expected positive float, got \'%s\'', $calculatedVal));
		Interval::{$method}($val);
	}

	/**
	 * @param $method
	 * @param $val
	 * @param $expected
	 * @dataProvider oneAndUpProvider
	 */
	public function testItCalculatesTheCorrectInterval($method, $val, $expected): void
	{
		$this->setName('It calculates the correct interval in ' . $method);
		$interval = Interval::{$method}($val);

		self::assertEquals($expected, $interval->asFloat());
	}

	public function zeroAndDownProvider(): Generator
	{
		return $this->createProvider(0, -5, -9000);
	}

	public function oneAndUpProvider(): Generator
	{
		return $this->createProvider(1, 5, 7500);
	}

	public function createProvider(...$args): Generator
	{
		foreach ($args as $arg) {
			yield "$arg seconds" => ['seconds', $arg, $arg];
			yield "$arg minutes" => ['minutes', $arg, $arg * 60.0];
			yield "$arg hours" => ['hours', $arg, $arg * 3600.0];
		}
	}
}
