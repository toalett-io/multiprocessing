<?php

namespace Toalett\Multiprocessing\Tests\Tools;

use ReflectionObject;

trait PropertyInspector
{
	protected function getProperty(object $object, string $propertyName)
	{
		$reflector = new ReflectionObject($object);
		$property = $reflector->getProperty($propertyName);
		$property->setAccessible(true);
		return $property->getValue($object);
	}
}
