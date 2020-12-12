# 🚽 Toalett

Welcome to Toalett, a humble initiative based around the idea that all software is 💩.  
Toalett is the Norwegian word for toilet. It feels fancier than plain "toilet".

## Why `toalett/multiprocessing`?
[Multiprocessing](https://nl.wikipedia.org/wiki/Multiprocessing) is a technique that is often used in PHP (cli) applications to execute tasks asynchronously.
Due to the lack of native [multithreading](https://en.wikipedia.org/wiki/Multithreading_(computer_architecture)) in PHP, developers have to rely on
good old multiprocessing to do this.  

We often see code that's written in a quick and dirty way to accomplish this task, with calls to
`pcntl_fork()` hidden somewhere, leading to ugly implementations.

Toalett has nothing against quick and dirty PHP code. Toalett lives it. It _breathes_ it.
But since multiprocessing so common, it might be nice to use this library.

## Okay, cool, but... How?
`toalett/multiprocessing` comes with the handy-dandy `ContextBuilder` class which is used to build a `Context`. 
A `Context` is the central component of this library. It schedules tasks to the `Workers`. 
Workers are a representation of child processes that are working on a task.

The Context uses a [ReactPHP EventLoop](https://reactphp.org/event-loop/) internally 
and emits events using the simple (but elegant) [Evenement](https://github.com/igorw/Evenement) library.

## Events

The context emits events when something of interest happens.
You can react to these events by calling:  
`$context->on('name_of_event', fn() => ...);`.

These are the events emitted by the context:

1. `booted`
2. `worker_started`
3. `worker_stopped`
4. `congestion`
5. `congestion_relieved`
6. `no_workers_remaining`
7. `stopped`

#### 1. `booted`
This event is emitted after `$context->run()` is called.
This is the very first event dispatched by the context. 
It is dispatched as soon as the event loop has started.

#### 2. `worker_started`
This event is emitted when a worker has been started (the process has been forked).
The PID of the child process is supplied as an argument to a listener.

#### 3. `worker_stopped`
This event is emitted when a worker has been stopped (child process has stopped).
The PID of the child process is supplied as an argument to a listener.

#### 4. `congestion`
This event is emitted when the imposed concurrency limit is reached.
This happens when (for example) the concurrency is set to at most 2 child processes, 
and a third task gets submitted while 2 tasks are already running. 
The system naively waits for a child to stop before starting another worker.

#### 5. `congestion_relieved`
This event is emitted when congestion is relieved. 
This means that a child has stopped, allowing for the execution of a new task.

#### 6. `no_workers_remaining`
This event is emitted when there are no workers left running. 
This usually means there is no more work to do. 
It's possible to automatically stop the context when this event occurs. 
This is shown in the first and last example.

#### 7. `stopped`
The context can be stopped by calling `$context->stop()`.
When the workers and the event loop are succesfully stopped, the context
emits a `stopped` event.

## Examples
For most developers, the quickest way to learn something is by looking at examples. 
Three examples are provided.

There is a simple example, which demonstrates event emission with the creation of 50 jobs.
A counter is incremented every time a job stops.
When all jobs are done, the context is stopped.

The cleanup interval is the interval at which the context checks for dead 
worker processes and reads their exit codes.
It defaults to 5 seconds and is in some examples explicitely set to a low 
value to improve example responsiveness.

### [Counting stopped workers using events](bin/counting_stopped_workers.php)
```php
<?php

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
This example is a bit more elaborate than the previous one.
It serves to demonstrate congestion and how it is handled by the context: 
the context simply blocks all execution until a worker stops and a spot becomes available.

Watch for the occurence of 'C' in the output. 
This denotes congestion: a worker could not be started.
```php
<?php

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
Since a task is really just a `Closure`, it's also possible to submit an object 
with an implementation of the `__invoke()` magic method. 

In this example, execution is limited to a single worker, and jobs are 
instances of the `Job` class.

```php
<?php

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
Tests can be found in the [src/Tests/](src/Tests) directory.
