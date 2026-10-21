<?php

declare(strict_types=1);

namespace Shared\Application\IntegrationEvent;

use Patchlevel\EventSourcing\Message\Message;

interface IntegrationEventPublisherInterface
{
    public function publish(IntegrationEventInterface $event, Message $source): void;
}
