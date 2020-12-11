<?php

namespace Toalett\Multiprocessing\Tests;

use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;
use Toalett\Multiprocessing\ConcurrencyLimit;
use Toalett\Multiprocessing\ContextBuilder;
use Toalett\Multiprocessing\Tests\Tools\PropertyInspector;

class ContextBuilderTest extends TestCase
{
	use PropertyInspector;

	public function testItIsImmutable(): void
	{
		$builder = ContextBuilder::create();
		$eventLoop = $this->createMock(LoopInterface::class);
		$limit = $this->createMock(ConcurrencyLimit::class);

		self::assertNotSame($builder->withEventLoop($eventLoop), $builder);
		self::assertNotSame($builder->withLimit($limit), $builder);
	}

	public function testItGivesBackANewContextEachTimeBuildIsInvoked(): void
	{
		$builder = ContextBuilder::create();

		self::assertNotSame($builder->build(), $builder->build());
	}

	public function testItCreatesANewContextWithUnlimitedConcurrencyWhenSupplyingNoArguments(): void
	{
		$builder = ContextBuilder::create();

		$context = $builder->build();
		self::assertIsObject($context);
		self::assertInstanceOf(LoopInterface::class, $this->getProperty($context, 'eventLoop'));

		/** @var ConcurrencyLimit|null $limit */
		$limit = $this->getProperty($context, 'limit');
		self::assertIsObject($limit);
		self::assertInstanceOf(ConcurrencyLimit::class, $limit);
		self::assertTrue($limit->isUnlimited());
	}

	public function testWhenSuppliedWithACustomEventLoopItUsesThatEventLoop(): void
	{
		$builder = ContextBuilder::create();
		$eventLoop = $this->createMock(LoopInterface::class);

		$context = $builder->withEventLoop($eventLoop)->build();
		$usedEventLoop = $this->getProperty($context, 'eventLoop');

		self::assertSame($eventLoop, $usedEventLoop);
	}

	public function testWhenSuppliedWithACustomConcurrencyLimitItUsesThatLimit(): void
	{
		$builder = ContextBuilder::create();
		$limit = $this->createMock(ConcurrencyLimit::class);

		$context = $builder->withLimit($limit)->build();
		$usedLimit = $this->getProperty($context, 'limit');

		self::assertSame($limit, $usedLimit);
	}
}
