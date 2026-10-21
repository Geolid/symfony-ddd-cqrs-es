<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\IntegrationEvent\OrderConfirmed;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Sales\Ordering\Domain\Order\Event\OrderConfirmed;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;
use Shared\Application\Mapper\PostalAddressMapper;

#[Publisher('sales.ordering.publish_order_confirmed')]
final readonly class OrderConfirmedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(OrderConfirmed::class)]
    public function __invoke(OrderConfirmed $event, Message $message): void
    {
        $this->publisher->publish(new OrderConfirmedIntegrationEvent(
            orderId: $event->id->toString(),
            cartId: $event->cartId,
            customerId: $event->customerId,
            checkoutSessionId: $event->checkoutSessionId,
            shippingAddress: PostalAddressMapper::toArray($event->shippingAddress),
            confirmedAt: $event->confirmedAt,
        ), $message);
    }
}
