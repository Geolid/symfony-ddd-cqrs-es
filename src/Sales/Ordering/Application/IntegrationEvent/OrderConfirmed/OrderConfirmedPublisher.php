<?php

declare(strict_types=1);

namespace Sales\Ordering\Application\IntegrationEvent\OrderConfirmed;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Ordering\Domain\Order\Event\OrderConfirmed;
use Sales\Ordering\Domain\Order\Exception\OrderNotFoundException;
use Sales\Ordering\Domain\Order\Order;
use Sales\Ordering\Domain\Order\Repository\OrderRepositoryInterface;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;
use Shared\Application\Mapper\PostalAddressMapper;

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
        $order = $this->orderRepository->load($event->id);

        $this->publisher->publish(Order::class, $event->id->toString(), new OrderConfirmedIntegrationEvent(
            orderId: $event->id->toString(),
            cartId: $order->cartId,
            shopperId: $order->shopperId,
            paymentId: $order->paymentId,
            shippingAddress: PostalAddressMapper::toArray($order->shippingAddress),
            confirmedAt: $event->confirmedAt,
        ));
    }
}
