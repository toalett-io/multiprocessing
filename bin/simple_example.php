<?php

use Toalett\Multiprocessing\ContextBuilder;
use Toalett\Multiprocessing\Task\Interval;

require_once __DIR__ . '/../vendor/autoload.php';

const NUM_JOBS = 50;

$counter = new class {
	public int $value = 0;

	public function increment(): void
	{
		$this->value++;
	}
};

$context = ContextBuilder::create()
	->withCleanupInterval(Interval::seconds(0.5))
	->build();

$context->on('worker_stopped', [$counter, 'increment']);
$context->on('no_workers_remaining', [$context, 'stop']);
$context->on('stopped', fn() => printf("\nJobs completed: %d\n", $counter->value));

for ($i = 0; $i < NUM_JOBS; $i++) {
	$context->submit(fn() => sleep(3));
	print('.');
}

$context->run();
