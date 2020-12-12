<?php

use React\EventLoop\Factory;
use Toalett\Multiprocessing\Concurrency;
use Toalett\Multiprocessing\ContextBuilder;

require_once __DIR__ . '/../vendor/autoload.php';

$loop = Factory::create();
$context = ContextBuilder::create()
    ->withEventLoop($loop)
    ->withConcurrency(Concurrency::atMost(4))
    ->build();

$context->on('booted', fn() => print("🚽 Toalett Multiprocessing Context\n"));
$context->on('congestion', fn() => print('C'));
$context->on('congestion_relieved', fn() => print('R'));
$context->on('worker_started', fn() => print('+'));
$context->on('worker_stopped', fn() => print('-'));

$loop->addPeriodicTimer(1, fn() => $context->submit(fn(int $s) => sleep($s), random_int(0, 10)));

print("Press CTRL+C to stop.\n");
$context->run();
