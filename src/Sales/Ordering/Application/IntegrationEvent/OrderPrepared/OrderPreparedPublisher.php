<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\IntegrationEvent\OrderPrepared;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Sales\Ordering\Domain\Order\Event\OrderPrepared;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.ordering.publish_order_prepared')]
final readonly class OrderPreparedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(OrderPrepared::class)]
    public function __invoke(OrderPrepared $event, Message $message): void
    {
        $this->publisher->publish(new OrderPreparedIntegrationEvent(
            orderId: $event->id->toString(),
            preparedAt: $event->preparedAt,
        ), $message);
    }
}
