<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Persistence\EventStore\Publisher;

use Shared\Application\Event\IntegrationEventInterface;

interface IntegrationEventAppenderInterface
{
    /**
     * @param class-string $aggregateClass
     */
    public function append(string $aggregateClass, string $aggregateId, IntegrationEventInterface $event): void;
}
