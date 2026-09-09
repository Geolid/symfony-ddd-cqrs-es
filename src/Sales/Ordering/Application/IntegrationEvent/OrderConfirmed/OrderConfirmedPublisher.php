<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\IntegrationEvent\OrderConfirmed;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Domain\Event\OrderConfirmed;
use Sales\Ordering\Domain\Exception\OrderNotFoundException;
use Sales\Ordering\Domain\Order;
use Sales\Ordering\Domain\Repository\OrderRepositoryInterface;
use Sales\Ordering\Domain\ValueObject\OrderId;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.ordering.publish_order_confirmed')]
final readonly class OrderConfirmedPublisher
{
    public function __construct(
        private IntegrationEventPublisherInterface $publisher,
        private OrderRepositoryInterface $orderRepository,
    ) {
    }

    /**
     * @throws OrderNotFoundException
     */
    #[Subscribe(OrderConfirmed::class)]
    public function __invoke(OrderConfirmed $event): void
    {
        $order = $this->orderRepository->load(OrderId::fromString($event->id));

        $this->publisher->publish(Order::class, $event->id, new OrderConfirmedIntegrationEvent(
            orderId: $event->id,
            buyerId: $order->buyerId,
            shippingAddress: $order->shippingAddress->toArray(),
            confirmedAt: $event->confirmedAt,
        ));
    }
}
