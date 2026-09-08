<?php

declare(strict_types=1);

namespace Support\TestCase;

trait SubscriberTrait
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
     * Invokes a #[Policy] or #[Processor] class directly with $event, bypassing the subscription bus.
     *
     * @param class-string $subscriberClass
     */
    protected function trigger(string $subscriberClass, object $event): void
    {
        $subscriber = $this->service($subscriberClass);
        \assert(\is_callable($subscriber));
        $subscriber($event);
    }
}
