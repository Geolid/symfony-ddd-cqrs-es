<?php

declare(strict_types=1);

namespace Support\Helpers;

use Psr\Container\ContainerInterface;

trait ServiceLocatorTrait
{
    abstract public static function getContainer(): ContainerInterface;

    /**
     * Retrieves a service by its class name and ensures its type.
     *
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

    /**
     * Retrieves a service by ID and ensures it matches the specified type.
     *
     * @template T of object
     *
     * @param class-string<T> $type
     *
     * @return T
     */
    protected function serviceAs(string $serviceId, string $type): object
    {
        $service = static::getContainer()->get($serviceId);

        \assert($service instanceof $type);

        return $service;
    }
}
