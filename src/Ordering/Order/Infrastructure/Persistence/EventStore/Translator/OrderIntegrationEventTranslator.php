<?php

declare(strict_types=1);

namespace Ordering\Order\Infrastructure\Persistence\EventStore\Translator;

use Ordering\Order\Application\Event\OrderCancelledIntegrationEvent;
use Ordering\Order\Application\Event\OrderPlacedIntegrationEvent;
use Ordering\Order\Domain\Event\OrderCancelled;
use Ordering\Order\Domain\Event\OrderPlaced;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Infrastructure\Persistence\EventStore\Translator\AbstractIntegrationEventTranslator;
use Shared\Infrastructure\Persistence\EventStore\Translator\Translator;

/**
 * Translates Ordering's Domain Events into its public Integration Event contract. This is the
 * ONLY place a Domain Event crosses the BC boundary — Shipping subscribes to
 * OrderPlacedIntegrationEvent/OrderCancelledIntegrationEvent, never to the Domain Events
 * OrderPlaced/OrderCancelled.
 */
#[Translator('ordering.order.integration_translator')]
final readonly class OrderIntegrationEventTranslator extends AbstractIntegrationEventTranslator
{
    #[Subscribe(OrderPlaced::class)]
    public function onOrderPlaced(OrderPlaced $event): void
    {
        $this->append(
            \sprintf('ordering.order.integration.%s', $event->id),
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
            \sprintf('ordering.order.integration.%s', $event->id),
            new OrderCancelledIntegrationEvent(
                orderId: $event->id,
                cancelledAt: $event->cancelledAt,
            ),
        );
    }
}
