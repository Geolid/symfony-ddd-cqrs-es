<?php

declare(strict_types=1);

namespace Shared\Application\IntegrationEvent;

use Patchlevel\EventSourcing\Message\Message;

interface IntegrationEventPublisherInterface
{
    /**
     * @param class-string $aggregateClass
     */
    public function publish(string $aggregateClass, string $aggregateId, IntegrationEventInterface $event, Message $source): void;
}
