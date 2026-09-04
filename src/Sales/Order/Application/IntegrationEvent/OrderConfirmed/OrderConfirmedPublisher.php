<?php

declare(strict_types=1);

namespace Sales\Order\Application\IntegrationEvent\OrderConfirmed;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Domain\Event\OrderConfirmed;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.order.publish_order_confirmed')]
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
            customerId: $order->customerId,
            shippingAddress: $order->shippingAddress->toArray(),
            confirmedAt: $event->confirmedAt,
        ));
    }
}
