<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\IntegrationEvent\OrderAborted;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Domain\Event\OrderAborted;
use Sales\Ordering\Domain\Exception\OrderNotFoundException;
use Sales\Ordering\Domain\Order;
use Sales\Ordering\Domain\Repository\OrderRepositoryInterface;
use Sales\Ordering\Domain\ValueObject\OrderId;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.ordering.publish_order_aborted')]
final readonly class OrderAbortedPublisher
{
    public function __construct(
        private IntegrationEventPublisherInterface $publisher,
        private OrderRepositoryInterface $repository,
    ) {
    }

    /**
     * @throws OrderNotFoundException
     */
    #[Subscribe(OrderAborted::class)]
    public function __invoke(OrderAborted $event): void
    {
        $order = $this->repository->load(OrderId::fromString($event->id));

        $this->publisher->publish(Order::class, $event->id, new OrderAbortedIntegrationEvent(
            orderId: $event->id,
            buyerId: $order->buyerId,
            abortedAt: $event->abortedAt,
        ));
    }
}
