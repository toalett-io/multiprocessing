<?php

namespace Toalett\Multiprocessing\Tests;

use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;
use Toalett\Multiprocessing\ConcurrencyLimit;
use Toalett\Multiprocessing\ContextBuilder;
use Toalett\Multiprocessing\Tests\Tools\PropertyInspector;
use Toalett\Multiprocessing\Workers;

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

	public function testItBuildsANewContextEveryTime(): void
	{
		$builder = ContextBuilder::create();

		self::assertNotSame($builder->build(), $builder->build());
	}

	public function testTheDefaultConcurrencyLimitIsUnlimited(): void
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

	public function testWhenGivenAnEventLoopItUsesThatLoop(): void
	{
		$builder = ContextBuilder::create();
		$eventLoop = $this->createMock(LoopInterface::class);

		$context = $builder->withEventLoop($eventLoop)->build();
		$usedEventLoop = $this->getProperty($context, 'eventLoop');

		self::assertSame($eventLoop, $usedEventLoop);
	}

	public function testWhenGivenAConcurrencyLimitItUsesThatLimit(): void
	{
		$builder = ContextBuilder::create();
		$limit = $this->createMock(ConcurrencyLimit::class);

		$context = $builder->withLimit($limit)->build();
		$usedLimit = $this->getProperty($context, 'limit');

		self::assertSame($limit, $usedLimit);
	}

	public function testWhenGivenWorkersItUsesThatWorkers(): void
	{
		$builder = ContextBuilder::create();
		$workers = $this->createMock(Workers::class);

		$context = $builder->withWorkers($workers)->build();
		$usedWorkers = $this->getProperty($context, 'workers');

		self::assertSame($workers, $usedWorkers);
	}
}
