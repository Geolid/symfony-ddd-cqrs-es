<?php

declare(strict_types=1);

namespace Sales\Order\Application\IntegrationEvent\OrderCancelled;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.order.publish_order_cancelled')]
final readonly class OrderCancelledPublisher
{
    public function __construct(
        private IntegrationEventPublisherInterface $publisher,
        private OrderRepositoryInterface $repository,
    ) {
    }

    /**
     * @throws OrderNotFoundException
     */
    #[Subscribe(OrderCancelled::class)]
    public function __invoke(OrderCancelled $event): void
    {
        $order = $this->repository->load(OrderId::fromString($event->id));

        $this->publisher->publish(Order::class, $event->id, new OrderCancelledIntegrationEvent(
            orderId: $event->id,
            buyerId: $order->buyerId,
            cancelledAt: $event->cancelledAt,
        ));
    }
}
