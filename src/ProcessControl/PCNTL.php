<?php

namespace Toalett\Multiprocessing\ProcessControl;

class PCNTL implements ProcessControl
{
    public function fork(): Fork
    {
        $pid = pcntl_fork();
        return new Fork($pid);
    }

    public function wait(int $options = 0): Wait
    {
        $pid = pcntl_wait($status, $options);
        return new Wait($pid, $status);
    }
}
