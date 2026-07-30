<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\EventStore\Translator;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Event\OrderCancelledIntegrationEvent;
use Sales\Order\Application\Event\OrderPlacedIntegrationEvent;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Order\Domain\Event\OrderPlaced;
use Shared\Infrastructure\Persistence\EventStore\Translator\AbstractIntegrationEventTranslator;
use Shared\Infrastructure\Persistence\EventStore\Translator\Translator;

#[Translator('sales.order.integration_translator')]
final readonly class OrderIntegrationEventTranslator extends AbstractIntegrationEventTranslator
{
    #[Subscribe(OrderPlaced::class)]
    public function onOrderPlaced(OrderPlaced $event): void
    {
        $this->append(
            \sprintf('sales.order.integration.%s', $event->id),
            new OrderPlacedIntegrationEvent(
                orderId: $event->id,
                customerId: $event->customerId,
                totalAmountInCents: $event->totalAmountInCents,
                placedAt: $event->placedAt,
            ),
        );
    }

    #[Subscribe(OrderCancelled::class)]
    public function onOrderCancelled(OrderCancelled $event): void
    {
        $this->append(
            \sprintf('sales.order.integration.%s', $event->id),
            new OrderCancelledIntegrationEvent(
                orderId: $event->id,
                cancelledAt: $event->cancelledAt,
            ),
        );
    }
}
