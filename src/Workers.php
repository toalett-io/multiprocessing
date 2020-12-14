<?php

namespace Toalett\Multiprocessing;

use Countable;
use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use Throwable;
use Toalett\Multiprocessing\Exception\ProcessControlException;
use Toalett\Multiprocessing\ProcessControl\PCNTL;
use Toalett\Multiprocessing\ProcessControl\ProcessControl;
use Toalett\Multiprocessing\ProcessControl\Wait;

class Workers implements Countable, EventEmitterInterface
{
    use EventEmitterTrait;

    /** @var int[] */
    private array $workers = [];
    private ProcessControl $processControl;

    public function __construct(?ProcessControl $processControl = null)
    {
        $this->processControl = $processControl ?? new PCNTL();
    }

    public function count(): int
    {
        return count($this->workers);
    }

    public function createWorkerFor(callable $task, array $args = []): void
    {
        $pid = $this->forkWorker($task, $args);
        $this->workers[$pid] = $pid;
        $this->emit('worker_started', [$pid]);
    }

    public function cleanup(): void
    {
        while (true === $this->wait(Wait::NO_HANG)) ;
        if (0 === count($this)) {
            $this->emit('no_workers_remaining');
        }
    }

    public function awaitCongestionRelief(): void
    {
        $this->wait();
    }

    private function remove(int $pid): void
    {
        unset($this->workers[$pid]);
        $this->emit('worker_stopped', [$pid]);
    }

    private function forkWorker(callable $task, array $args): int
    {
        $fork = $this->processControl->fork();
        if ($fork->failed()) {
            throw ProcessControlException::forkFailed();
        }

        if ($fork->isChild()) {
            try {
                call_user_func_array($task, $args);
            } catch (Throwable $t) {
                fwrite(STDERR, $t->getMessage());
                exit(1);
            }
            exit(0);
        }

        return $fork->pid;
    }

    /**
     * @param int $options
     * @return bool Whether a process was caught
     */
    private function wait(int $options = 0): bool
    {
        $wait = $this->processControl->wait($options);
        if ($wait->childStopped()) {
            $this->remove($wait->pid);
            return true;
        }

        return false;
    }

    public function stop(): void
    {
        while (true === $this->wait()) ;
    }
}
