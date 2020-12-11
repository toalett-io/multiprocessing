<?php

namespace Toalett\Multiprocessing\ProcessControl;

interface ProcessControl
{
	public function fork(): Fork;

	public function wait(int $options = 0): Wait;
}
