<?php

namespace Toalett\Multiprocessing\ProcessControl;

class Fork
{
	public int $pid;

	public function __construct(int $pid)
	{
		$this->pid = $pid;
	}

	public function failed(): bool
	{
		return $this->pid < 0;
	}

	public function isChild(): bool
	{
		return $this->pid === 0;
	}

	public function isParent(): bool
	{
		return $this->pid !== 0;
	}
}
