<?php

declare(strict_types=1);

namespace Sales\Order\Application\IntegrationEvent\OrderPaymentRequested;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Domain\Event\OrderPaymentRequested;
use Sales\Order\Domain\Order;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.order.publish_order_payment_requested')]
final readonly class OrderPaymentRequestedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(OrderPaymentRequested::class)]
    public function __invoke(OrderPaymentRequested $event): void
    {
        $this->publisher->publish(Order::class, $event->orderId, new OrderPaymentRequestedIntegrationEvent(
            orderId: $event->orderId,
            amountInCents: $event->amountInCents,
            reference: $event->reference,
            checkoutUrl: $event->checkoutUrl,
            requestedAt: $event->requestedAt,
        ));
    }
}
