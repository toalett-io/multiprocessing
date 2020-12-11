# 🚽 Toalett

Welcome to Toalett, a new initiative, based on the idea that all software is 💩. Toalett is the Norwegian word for toilet. It feels fancier than plain "toilet".

## Why `toalett/multiprocessing`?
[Multiprocessing](https://nl.wikipedia.org/wiki/Multiprocessing) is a technique that is often used in PHP applications to execute tasks asynchronously.
Due to the lack of native [multithreading](https://en.wikipedia.org/wiki/Multithreading_(computer_architecture)) in PHP, developers have to rely on
good old multiprocessing to do this.  

We often see code that's written in a quick and dirty way to accomplish this task, with calls to
`pcntl_fork()` hidden somewhere, leading to ugly implementations.

Now, I from Toalett have nothing against quick and dirty PHP code. I live it. I breathe it. 
But since multiprocessing so common, it might be nice to use this library.

## Okay, cool, but... How?
`toalett/multiprocessing` comes with the handy-dandy `ContextBuilder` class which is used to, well, _build_ a _Context_. 
The Context is the central component of this library. It schedules tasks to the _Workers_. 
Workers are a representation of child processes that are working on a task.



The Context uses a [ReactPHP EventLoop](https://reactphp.org/event-loop/) internally and emits events using the simple (but quite elegant) [Evenement](https://github.com/igorw/Evenement) library.

## Examples
For most developers, the quickest way to learn something is by looking at examples. 
Three examples are provided.

There is a simple example, which

### [Simple example](bin/simple_example.php)
```php
<?php

use Toalett\Multiprocessing\ContextBuilder;

require_once 'path/to/autoload.php';

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
```

### [More elaborate example](bin/more_elaborate_example.php)
This example is a bit more elaborate than the previous one.
It serves to demonstrate congestion and how it is handled by the context: 
the context simply blocks all execution until a worker stops and a spot becomes available.

This example also makes more use of the events (described [here](## Events)).
```php
<?php

use Toalett\Multiprocessing\ContextBuilder;
use Toalett\Multiprocessing\ConcurrencyLimit;
use React\EventLoop\Factory as EventLoopFactory;

require_once 'path/to/autoload.php';

// Create our own EventLoop and limit and supply them to the builder
$loop = EventLoopFactory::create();
$limit = new ConcurrencyLimit(4);
$context = ContextBuilder::create()->withEventLoop($loop)->withLimit($limit)->build();

$context->on('booted', fn() => print("🚽 Toalett Multiprocessing Context\n"));
$context->on('congestion', fn() => print('C'));
$context->on('congestion_relieved', fn() => print('R'));
$context->on('worker_started', fn() => print('+'));
$context->on('worker_stopped', fn() => print('-'));

// Submit a fake job every second
$loop->addPeriodicTimer(1, fn() => $context->submit(fn(int $s) => sleep($s), random_int(0, 10)));

print("Press CTRL+C to stop.\n");
$context->run();

```

### [Example with a Job class](bin/example_with_job_class.php)
Since the task is defined by a `callable` supplied with arguments, it's also possible to 
define a class that implements the magic `__invoke()` method and submit objects of this 
class to the Context. Objects implementing the `__invoke()` method can be treated as 
closures. They may accept zero or more arguments. 

This idea is demonstrated here, while execution is limited to a single worker.
```php
<?php

use Toalett\Multiprocessing\ConcurrencyLimit;
use Toalett\Multiprocessing\ContextBuilder;

require_once 'path/to/autoload.php';

class Job
{
	private string $title;

	public function __construct(string $title)
	{
		$this->title = $title;
	}

	public function __invoke()
	{
		cli_set_process_title('php ' . $this->title);
		print("start:{$this->title}\n");
		sleep(3);
		print("stop :{$this->title}\n");
	}
}

$limit = ConcurrencyLimit::singleWorker();
$context = ContextBuilder::create()->withLimit($limit)->build();

for ($i = 0; $i < 3; $i++) {
	$title = md5(mt_rand());
	$context->submit(new Job($title));
}

$context->on('no_workers_remaining', fn() => $context->stop());
$context->run();
```

## Events

1. `booted`
1. `worker_started`
1. `worker_stopped`
1. `congestion`
1. `congestion_relieved`
1. `no_workers_remaining`
1. `stopped`

These events are emitted by the `Context`. 
The `worker_started` and `worker_stopped` events are emitted by the `Workers` under the hood, 
but they are proxied through the `Context` in order to unify access to them.

#### `booted`
This event is emitted when `$context->run()` is called.
This is the first event dispatched by the `Context`.

#### `worker_started`
This event is emitted when a worker has been started (the process has been forked).
The PID of the child process is supplied as an argument to a listener.

#### `worker_stopped`
This event is emitted when a worker has been stopped (child process has stopped).
The PID of the child process is supplied as an argument to a listener.

#### `congestion`
This event is emitted when the imposed concurrency limit is reached, for example, 
when the limit is set to at most 2 child processes, and a third task gets submitted
while there are already two tasks running. 
The system naively waits for a child to stop before starting another worker.

#### `congestion_relieved`
This event is emitted in case the congestion explained above is relieved. 
This means that a child has stopped, allowing the execution of a new task.

#### `no_workers_remaining`
This event is emitted when there are no workers left running. 
This usually means there is no more work to do. 
It's possible to automatically stop the context when this event occurs. 
This is shown in the second and last example.

#### `stopped`
This event is emitted when `$context->stop()` is called and the eventloop has
succesfully been stopped.

## Why no shared memory?
Shared memory in PHP is hard to manage and quickly becomes a mess.

Don't ask.
