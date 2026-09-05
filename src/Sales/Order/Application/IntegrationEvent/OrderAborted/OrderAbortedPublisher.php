<?php

declare(strict_types=1);

namespace Sales\Order\Application\IntegrationEvent\OrderAborted;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Domain\Event\OrderAborted;
use Sales\Order\Domain\Order;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.order.publish_order_aborted')]
final readonly class OrderAbortedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(OrderAborted::class)]
    public function __invoke(OrderAborted $event): void
    {
        $this->publisher->publish(Order::class, $event->id, new OrderAbortedIntegrationEvent(
            orderId: $event->id,
            abortedAt: $event->abortedAt,
        ));
    }
}
