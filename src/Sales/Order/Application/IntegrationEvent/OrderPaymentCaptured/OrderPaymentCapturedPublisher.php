<?php

declare(strict_types=1);

namespace Sales\Order\Application\IntegrationEvent\OrderPaymentCaptured;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Domain\Event\OrderPaymentCaptured;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('sales.order.publish_order_payment_captured')]
final readonly class OrderPaymentCapturedPublisher
{
    public function __construct(
        private IntegrationEventPublisherInterface $publisher,
        private OrderRepositoryInterface $orderRepository,
    ) {
    }

    /**
     * @throws OrderNotFoundException
     */
    #[Subscribe(OrderPaymentCaptured::class)]
    public function __invoke(OrderPaymentCaptured $event): void
    {
        $order = $this->orderRepository->load(OrderId::fromString($event->orderId));

        $this->publisher->publish(Order::class, $event->orderId, new OrderPaymentCapturedIntegrationEvent(
            orderId: $event->orderId,
            buyerId: $order->buyerId,
            shippingAddress: $order->shippingAddress->toArray(),
            capturedAt: $event->capturedAt,
        ));
    }
}
