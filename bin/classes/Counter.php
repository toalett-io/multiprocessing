<?php

class Counter
{
    public int $value = 0;

    public function increment(): void
    {
        $this->value++;
    }
}
