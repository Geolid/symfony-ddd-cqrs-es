<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\IntegrationEvent\OrderPaid;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Sales\Ordering\Domain\Order\Event\OrderPaid;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.ordering.publish_order_paid')]
final readonly class OrderPaidPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(OrderPaid::class)]
    public function __invoke(OrderPaid $event, Message $message): void
    {
        $this->publisher->publish(new OrderPaidIntegrationEvent(
            orderId: $event->id->toString(),
            paidAt: $event->paidAt,
        ), $message);
    }
}
