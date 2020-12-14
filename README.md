# 🚽 Toalett

Welcome to Toalett, a humble initiative based around the idea that all software is 💩.  
Toalett is the Norwegian word for toilet. It feels fancier than plain "toilet".

## Why `toalett/multiprocessing`?

[Multiprocessing](https://nl.wikipedia.org/wiki/Multiprocessing) is a technique that is often used in PHP (cli)
applications to execute tasks asynchronously. Due to the lack of
native [multithreading](https://en.wikipedia.org/wiki/Multithreading_(computer_architecture)) in PHP, developers have to
rely on good old multiprocessing to do this.

We often see code that's written in a quick and dirty way to accomplish this task, with calls to
`pcntl_fork()` hidden somewhere, leading to ugly implementations.

Toalett has nothing against quick and dirty PHP code, but since multiprocessing so common, it might be nice to use this
library.

## Okay, how do I use it?

### Structure

The library provides a single class to manage multiprocessing: the [`Context`](src/Context.php). It
uses [`react/event-loop`](https://reactphp.org/event-loop/) internally and emits events using the simple (but
elegant) [`evenement/evenement`](https://github.com/igorw/Evenement) library. It delegates tasks to the
internal [`Workers`](src/Workers.php) component, which in turn is responsible for creating and managing child processes.

### Creating a [`Context`](src/Context.php)

This library comes with the [`ContextBuilder`](src/ContextBuilder.php) class which is used to build
a [`Context`](src/Context.php). It can be supplied with a [`Concurrency`](src/Concurrency.php) limit (defaults to
unlimited), a custom instance of `\React\EventLoop\LoopInterface` and an [`Interval`](src/Task/Interval.php) at which a
cleanup of child processes should be performed. To create a [`Context`](src/Context.php), you simply call the `build()`
method:

```php
use Toalett\Multiprocessing\ContextBuilder;

$builder = ContextBuilder::create();
$context = $builder->build();
```

### Submitting a job

Use the `Context::submit` method to submit a job:

```php
use Toalett\Multiprocessing\ContextBuilder;

$context = ContextBuilder::create()->build();

$job = static function(string $name) {
    print("Hello from {$name}!\n");
    usleep(500_000);
    print("Goodbye from ${name}!\n");
};

$context->submit($job, 'John Snow');
```

Jobs are not executed until the `Context::run` method is called.  
In order to execute this job 5 times, using at most two processes, we would do:

```php
use Toalett\Multiprocessing\Concurrency;
use Toalett\Multiprocessing\ContextBuilder;

$context = ContextBuilder::create()
    ->withConcurrency(Concurrency::atMost(2))
    ->build();

// $job = function(...)...

foreach(['John', 'Stannis', 'Jorah', 'Robert', 'Daario'] as $name) {
    $context->submit($job, $name);
}

$context->run();
```

If you want to submit a job using an interval, you are encouraged to use a custom event loop instead of `sleep()`
or `usleep()` to prevent blocking the main process (and thus pausing the event loop):

```php
use React\EventLoop\Factory;
use Toalett\Multiprocessing\ContextBuilder;

$loop = Factory::create();
$context = ContextBuilder::create()
    ->withEventLoop($loop)
    ->build();

// Submit a job every 5 seconds
$loop->addPeriodicTimer(5.0, fn() => $context->submit(...));
$context->run();
```

## Events

The context emits events when something of interest happens. You can add event listeners using the `Context::on` method:

```php
$context->on('name_of_event', fn() => ...);
```

These are the events emitted by the context:

1. `booted`
2. `worker_started`
3. `worker_stopped`
4. `congestion`
5. `congestion_relieved`
6. `no_workers_remaining`
7. `stopped`

#### 1. The `booted` event

This event is emitted after `$context->run()` is called. This is the very first event dispatched by the context. It is
dispatched as soon as the event loop has started.

#### 2. The `worker_started` event

This event is emitted when a worker has been started (the process has been forked). The PID of the child process is
supplied as an argument to a listener.

#### 3. The `worker_stopped` event

This event is emitted when a worker has been stopped (child process has stopped). The PID of the child process is
supplied as an argument to a listener.

#### 4. The `congestion` event

This event is emitted when the imposed concurrency limit is reached. This happens when (for example) the concurrency is
set to at most 2 child processes, and a third task gets submitted while 2 tasks are already running. The system naively
waits for a child to stop before starting another worker.

#### 5. The `congestion_relieved` event

This event is emitted when congestion is relieved. This means that a child has stopped, allowing for the execution of a
new task.

#### 6. The `no_workers_remaining` event

This event is emitted when there are no workers left running. This usually means there is no more work to do. It's
possible to automatically stop the context when this event occurs. This is shown in the first and last example.

#### 7. The `stopped` event

The context can be stopped by calling `Context::stop`. When the workers and the event loop are succesfully stopped, the
context emits a `stopped` event.

## Examples

For most developers, the quickest way to learn something is by looking at examples. Three executable examples are
provided.

### [Counting stopped workers using events](bin/counting_stopped_workers.php)

This is a simple example, which demonstrates event emission with the creation of 50 jobs. A counter is incremented every
time a job stops. When all jobs are done, the context is stopped.

The cleanup interval may be set to a low value to improve responsiveness.

```php
use Toalett\Multiprocessing\ContextBuilder;
use Toalett\Multiprocessing\Task\Interval;

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
```

### [Triggering congestion with 4 workers](bin/triggering_congestion.php)

This example is a bit more elaborate than the previous one. It serves to demonstrate congestion and how it is handled by
the context:
the context simply blocks all execution until a worker stops and a spot becomes available.

Watch for the occurence of 'C' in the output. This denotes congestion: a worker could not be started.

```php
use React\EventLoop\Factory;
use Toalett\Multiprocessing\ContextBuilder;
use Toalett\Multiprocessing\Concurrency;

$loop = Factory::create();
$context = ContextBuilder::create()
    ->withEventLoop($loop)
    ->withConcurrency(Concurrency::atMost(4))
    ->build();

$context->on('booted', fn() => print("🚽 toalett context booted\n"));
$context->on('congestion', fn() => print('C'));
$context->on('congestion_relieved', fn() => print('R'));
$context->on('worker_started', fn() => print('+'));
$context->on('worker_stopped', fn() => print('-'));

// A job is submitted to the context every second.
// The job sleeps for a random amount of seconds (0 - 10).
$loop->addPeriodicTimer(1, fn() => $context->submit(fn(int $s) => sleep($s), random_int(0, 10)));

print("Press CTRL+C to stop.\n");
$context->run();
```

### [Single worker with a Job class](bin/single_worker_with_job_class.php)

Since a task is really just a [`Closure`](https://www.php.net/manual/en/class.closure.php), it's also possible to submit
an object with an implementation of the `__invoke()` magic method.

In this example, execution is limited to a single worker, and jobs are instances of the [`Job`](bin/classes/Job.php)
class.

```php
use Toalett\Multiprocessing\Concurrency;
use Toalett\Multiprocessing\ContextBuilder;
use Toalett\Multiprocessing\Task\Interval;

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
```

## Tests

Tests can be found in the [`src/Tests`](src/Tests) directory.
