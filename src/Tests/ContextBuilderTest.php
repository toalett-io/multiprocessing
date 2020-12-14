<?php

namespace Toalett\Multiprocessing\Tests;

use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;
use Toalett\Multiprocessing\Concurrency;
use Toalett\Multiprocessing\ContextBuilder;
use Toalett\Multiprocessing\Tests\Tools\PropertyInspector;

class ContextBuilderTest extends TestCase
{
    use PropertyInspector;

    public function testItIsImmutable(): void
    {
        $builder = ContextBuilder::create();
        $eventLoop = $this->createMock(LoopInterface::class);
        $concurrency = $this->createMock(Concurrency::class);

        self::assertNotSame($builder->withEventLoop($eventLoop), $builder);
        self::assertNotSame($builder->withConcurrency($concurrency), $builder);
    }

    public function testItBuildsANewContextEveryTime(): void
    {
        $builder = ContextBuilder::create();

        self::assertNotSame($builder->build(), $builder->build());
    }

    public function testTheDefaultConcurrencyIsUnlimited(): void
    {
        $builder = ContextBuilder::create();

        $context = $builder->build();
        self::assertIsObject($context);
        self::assertInstanceOf(LoopInterface::class, $this->getProperty($context, 'eventLoop'));

        /** @var Concurrency|null $concurrency */
        $concurrency = $this->getProperty($context, 'concurrency');
        self::assertIsObject($concurrency);
        self::assertInstanceOf(Concurrency::class, $concurrency);
        self::assertTrue($concurrency->isUnlimited());
    }

    public function testWhenGivenAnEventLoopItUsesThatLoop(): void
    {
        $builder = ContextBuilder::create();
        $eventLoop = $this->createMock(LoopInterface::class);

        $context = $builder->withEventLoop($eventLoop)->build();
        $usedEventLoop = $this->getProperty($context, 'eventLoop');

        self::assertSame($eventLoop, $usedEventLoop);
    }

    public function testWhenGivenAConcurrencyItUsesThatConcurrency(): void
    {
        $builder = ContextBuilder::create();
        $concurrency = $this->createMock(Concurrency::class);

        $context = $builder->withConcurrency($concurrency)->build();
        $usedConcurrency = $this->getProperty($context, 'concurrency');

        self::assertSame($concurrency, $usedConcurrency);
    }
}
