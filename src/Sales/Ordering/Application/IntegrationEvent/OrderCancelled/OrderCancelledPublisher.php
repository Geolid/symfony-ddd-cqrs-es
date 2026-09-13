<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\IntegrationEvent\OrderCancelled;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Domain\Order\Event\OrderCancelled;
use Sales\Ordering\Domain\Order\Exception\OrderNotFoundException;
use Sales\Ordering\Domain\Order\Order;
use Sales\Ordering\Domain\Order\Repository\OrderRepositoryInterface;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.ordering.publish_order_cancelled')]
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
        $order = $this->repository->load($event->id);

        $this->publisher->publish(Order::class, $event->id->toString(), new OrderCancelledIntegrationEvent(
            orderId: $event->id->toString(),
            customerId: $order->customerId,
            cancelledAt: $event->cancelledAt,
        ));
    }
}
