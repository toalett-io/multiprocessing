<?php

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
        print("* {$this->title}");
        sleep(1);
        print("\r  {$this->title}\n");
    }
}
