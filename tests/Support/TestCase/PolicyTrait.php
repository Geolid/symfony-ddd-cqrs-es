<?php

declare(strict_types=1);

namespace Support\TestCase;

trait PolicyTrait
{
    /**
     * @template T of object
     *
     * @param class-string<T> $serviceId
     *
     * @return T
     */
    abstract protected function service(string $serviceId): object;

    /**
     * Invokes a #[Policy] class directly with $event, bypassing the subscription bus.
     *
     * @param class-string $policyClass
     */
    protected function trigger(string $policyClass, object $event): void
    {
        ($this->service($policyClass))($event);
    }
}
