<?php

use Toalett\Multiprocessing\Concurrency;
use Toalett\Multiprocessing\ContextBuilder;
use Toalett\Multiprocessing\Task\Interval;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/classes/Job.php';

$context = ContextBuilder::create()
    ->withConcurrency(Concurrency::singleWorker())
    ->withCleanupInterval(Interval::seconds(0.2))
    ->build();

for ($i = 0; $i < 3; $i++) {
    $title = md5(mt_rand());
    $context->submit(new Job($title));
}

$context->on('no_workers_remaining', [$context, 'stop']);
$context->run();
