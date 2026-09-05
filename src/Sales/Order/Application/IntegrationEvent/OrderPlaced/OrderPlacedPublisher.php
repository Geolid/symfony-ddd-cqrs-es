<?php

declare(strict_types=1);

namespace Sales\Order\Application\IntegrationEvent\OrderPlaced;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Order\Domain\Order;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.order.publish_order_placed')]
final readonly class OrderPlacedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(OrderPlaced::class)]
    public function __invoke(OrderPlaced $event): void
    {
        $this->publisher->publish(Order::class, $event->id, new OrderPlacedIntegrationEvent(
            orderId: $event->id,
            buyerId: $event->buyerId,
            lines: $event->lines,
            totalAmountInCents: $event->totalAmount->cents,
            billingAddress: $event->billingAddress->toArray(),
            placedAt: $event->placedAt,
        ));
    }
}
