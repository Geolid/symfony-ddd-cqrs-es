<?php

declare(strict_types=1);

namespace Sales\Order\Application\IntegrationEvent\OrderCancelled;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Order\Domain\Order;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.order.order_cancelled_publisher')]
final readonly class OrderCancelledPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(OrderCancelled::class)]
    public function __invoke(OrderCancelled $event): void
    {
        $this->publisher->publish(Order::class, $event->id, new OrderCancelledIntegrationEvent(
            orderId: $event->id,
            cancelledAt: $event->cancelledAt,
        ));
    }
}
