<?php

declare(strict_types=1);

namespace Support\Helpers;

use Psr\Container\ContainerInterface;

trait ServiceLocatorTrait
{
    abstract public static function getContainer(): ContainerInterface;

    /**
     * @template T of object
     *
     * @param class-string<T> $serviceId
     *
     * @return T
     */
    protected function service(string $serviceId): object
    {
        $service = static::getContainer()->get($serviceId);

        \assert($service instanceof $serviceId);

        return $service;
    }
}
