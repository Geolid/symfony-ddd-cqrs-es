<?php

declare(strict_types=1);

namespace Sales\Order\Application\IntegrationEvent\OrderDelivered;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Domain\Event\OrderDelivered;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.order.publish_order_delivered')]
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
