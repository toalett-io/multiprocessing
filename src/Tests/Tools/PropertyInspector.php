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

    protected function setProperty(object $object, string $propertyName, $value): void
    {
        $reflector = new ReflectionObject($object);
        $property = $reflector->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
