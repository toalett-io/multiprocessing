<?php

use Toalett\Multiprocessing\ContextBuilder;
use Toalett\Multiprocessing\Task\Interval;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/classes/Counter.php';
const NUM_JOBS = 50;

$context = ContextBuilder::create()
    ->withCleanupInterval(Interval::seconds(0.5))
    ->build();

$counter = new Counter();
$context->on('worker_stopped', [$counter, 'increment']);
$context->on('no_workers_remaining', [$context, 'stop']);
$context->on('stopped', fn() => printf(" %d\n", $counter->value));

for ($i = 0; $i < NUM_JOBS; $i++) {
    $context->submit(fn() => sleep(2));
    print('.');
}

$context->run();
