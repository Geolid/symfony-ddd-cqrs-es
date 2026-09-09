<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\IntegrationEvent\OrderDelivered;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Domain\Order\Event\OrderDelivered;
use Sales\Ordering\Domain\Order\Exception\OrderNotFoundException;
use Sales\Ordering\Domain\Order\Order;
use Sales\Ordering\Domain\Order\Repository\OrderRepositoryInterface;
use Sales\Ordering\Domain\Order\ValueObject\OrderId;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.ordering.publish_order_delivered')]
final readonly class OrderDeliveredPublisher
{
    public function __construct(
        private IntegrationEventPublisherInterface $publisher,
        private OrderRepositoryInterface $repository,
    ) {
    }

    /**
     * @throws OrderNotFoundException
     */
    #[Subscribe(OrderDelivered::class)]
    public function __invoke(OrderDelivered $event): void
    {
        $order = $this->repository->load(OrderId::fromString($event->id));

        $this->publisher->publish(Order::class, $event->id, new OrderDeliveredIntegrationEvent(
            orderId: $event->id,
            buyerId: $order->buyerId,
            shippingAddress: $order->shippingAddress->toArray(),
            deliveredAt: $event->deliveredAt,
        ));
    }
}
