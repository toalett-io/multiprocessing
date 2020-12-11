<?php

use Toalett\Multiprocessing\ContextBuilder;

require_once __DIR__ . '/../vendor/autoload.php';

// We will run 50 jobs
const NUM_JOBS = 50;

$counter = new class {
	public int $value = 0;

	public function increment(): void
	{
		$this->value++;
	}
};

// Create a default context with unlimited concurrency
$context = ContextBuilder::create()->build();

// Each time a worker stops, a job is finished
$context->on('worker_stopped', fn() => $counter->increment());

// Automatically stop the context when there are no workers left
$context->on('no_workers_remaining', fn() => $context->stop());
$context->on('stopped', fn() => printf("Jobs completed: %d\n", $counter->value));

// You can submit jobs before the context is running. They will be executed
// in the order in which they are submitted to the context. They are
// scheduled on a future tick of the underlying event loop.
// Each job will involve sleeping for ~3 seconds in this example.
for ($i = 0; $i < NUM_JOBS; $i++) {
	$context->submit(fn() => sleep(3));
}

$context->run();
