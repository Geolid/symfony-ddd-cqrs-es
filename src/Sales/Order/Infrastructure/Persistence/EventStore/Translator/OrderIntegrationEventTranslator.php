<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\EventStore\Translator;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Event\OrderCancelledIntegrationEvent;
use Sales\Order\Application\Event\OrderPaymentCapturedIntegrationEvent;
use Sales\Order\Application\Event\OrderPaymentRequestedIntegrationEvent;
use Sales\Order\Application\Event\OrderPlacedIntegrationEvent;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Order\Domain\Event\OrderPaymentCaptured;
use Sales\Order\Domain\Event\OrderPaymentRequested;
use Sales\Order\Domain\Event\OrderPlaced;
use Shared\Infrastructure\Persistence\EventStore\Translator\AbstractIntegrationEventTranslator;
use Shared\Infrastructure\Persistence\EventStore\Translator\Translator;

#[Translator('sales.order.integration_translator')]
final readonly class OrderIntegrationEventTranslator extends AbstractIntegrationEventTranslator
{
    #[Subscribe(OrderPaymentRequested::class)]
    public function onOrderPaymentRequested(OrderPaymentRequested $event): void
    {
        $this->append(
            \sprintf('sales.order.integration.%s', $event->orderId),
            new OrderPaymentRequestedIntegrationEvent(
                orderId: $event->orderId,
                requestedAt: $event->requestedAt,
            ),
        );
    }

    #[Subscribe(OrderPlaced::class)]
    public function onOrderPlaced(OrderPlaced $event): void
    {
        $this->append(
            \sprintf('sales.order.integration.%s', $event->id),
            new OrderPlacedIntegrationEvent(
                orderId: $event->id,
                customerId: $event->customerId,
                buyerAddress: $event->buyerAddress,
                lines: $event->lines,
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

    #[Subscribe(OrderPaymentCaptured::class)]
    public function onOrderPaymentCaptured(OrderPaymentCaptured $event): void
    {
        $this->append(
            \sprintf('sales.order.integration.%s', $event->orderId),
            new OrderPaymentCapturedIntegrationEvent(
                orderId: $event->orderId,
                customerId: $event->customerId,
                buyerAddress: $event->buyerAddress,
                capturedAt: $event->capturedAt,
            ),
        );
    }
}
