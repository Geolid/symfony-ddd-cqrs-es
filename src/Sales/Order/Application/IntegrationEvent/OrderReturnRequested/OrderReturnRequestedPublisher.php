<?php

declare(strict_types=1);

namespace Sales\Order\Application\IntegrationEvent\OrderReturnRequested;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Domain\Event\OrderReturnRequested;
use Sales\Order\Domain\Order;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.order.order_return_requested_publisher')]
final readonly class OrderReturnRequestedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(OrderReturnRequested::class)]
    public function __invoke(OrderReturnRequested $event): void
    {
        $this->publisher->publish(Order::class, $event->id, new OrderReturnRequestedIntegrationEvent(
            orderId: $event->id,
            requestedAt: $event->requestedAt,
        ));
    }
}
