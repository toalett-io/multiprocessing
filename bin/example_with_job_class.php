<?php

use Toalett\Multiprocessing\ConcurrencyLimit;
use Toalett\Multiprocessing\ContextBuilder;
use Toalett\Multiprocessing\Task\Interval;

require_once __DIR__ . '/../vendor/autoload.php';

class Job
{
	private string $title;

	public function __construct(string $title)
	{
		$this->title = $title;
	}

	public function __invoke()
	{
		cli_set_process_title("php {$this->title}");
		print("+ {$this->title}");
		sleep(1);
		print("\r {$this->title}\n");
	}
}

$limit = ConcurrencyLimit::singleWorker();
$context = ContextBuilder::create()
	->withLimit(ConcurrencyLimit::singleWorker())
	->withCleanupInterval(Interval::seconds(0.2))
	->build();

for ($i = 0; $i < 3; $i++) {
	$title = md5(mt_rand());
	$context->submit(new Job($title));
}

$context->on('no_workers_remaining', [$context, 'stop']);
$context->run();
