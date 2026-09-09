<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\IntegrationEvent\OrderDispatched;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Domain\Order\Event\OrderDispatched;
use Sales\Ordering\Domain\Order\Order;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.ordering.publish_order_dispatched')]
final readonly class OrderDispatchedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(OrderDispatched::class)]
    public function __invoke(OrderDispatched $event): void
    {
        $this->publisher->publish(Order::class, $event->id, new OrderDispatchedIntegrationEvent(
            orderId: $event->id,
            dispatchedAt: $event->dispatchedAt,
        ));
    }
}
