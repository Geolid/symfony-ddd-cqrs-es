<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\IntegrationEvent\OrderFailed;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Domain\Order\Event\OrderFailed;
use Sales\Ordering\Domain\Order\Exception\OrderNotFoundException;
use Sales\Ordering\Domain\Order\Order;
use Sales\Ordering\Domain\Order\Repository\OrderRepositoryInterface;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.ordering.publish_order_failed')]
final readonly class OrderFailedPublisher
{
    public function __construct(
        private IntegrationEventPublisherInterface $publisher,
        private OrderRepositoryInterface $repository,
    ) {
    }

    /**
     * @throws OrderNotFoundException
     */
    #[Subscribe(OrderFailed::class)]
    public function __invoke(OrderFailed $event): void
    {
        $order = $this->repository->load($event->id);

        $this->publisher->publish(Order::class, $event->id->toString(), new OrderFailedIntegrationEvent(
            orderId: $event->id->toString(),
            customerId: $order->customerId,
            failedAt: $event->failedAt,
        ));
    }
}
