<?php

namespace Toalett\Multiprocessing\ProcessControl;

class Wait
{
	public const NO_HANG = WNOHANG;
	public const UNTRACED = WUNTRACED;
	public int $pid;
	public int $status;

	public function __construct(int $pid, int $status = 0)
	{
		$this->pid = $pid;
		$this->status = $status;
	}

	public function childStopped(): bool
	{
		return $this->pid > 0;
	}

	public function failed(): bool
	{
		return $this->pid < 0;
	}
}
