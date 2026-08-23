<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\EventStore\Publisher;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Application\Event\OrderCancelledIntegrationEvent;
use Sales\Order\Application\Event\OrderConfirmedIntegrationEvent;
use Sales\Order\Application\Event\OrderPaymentCapturedIntegrationEvent;
use Sales\Order\Application\Event\OrderPaymentRequestedIntegrationEvent;
use Sales\Order\Application\Event\OrderPlacedIntegrationEvent;
use Sales\Order\Application\Event\OrderReturnRequestedIntegrationEvent;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Order\Domain\Event\OrderConfirmed;
use Sales\Order\Domain\Event\OrderPaymentCaptured;
use Sales\Order\Domain\Event\OrderPaymentRequested;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Order\Domain\Event\OrderReturnRequested;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Domain\ValueObject\PostalAddress;
use Shared\Infrastructure\Persistence\EventStore\Publisher\IntegrationEventAppenderInterface;
use Shared\Infrastructure\Persistence\EventStore\Publisher\Publisher;

#[Publisher('sales.order.integration')]
final readonly class OrderPublisher
{
    public function __construct(
        private IntegrationEventAppenderInterface $appender,
        private OrderRepositoryInterface $orderRepository,
    ) {
    }

    #[Subscribe(OrderPlaced::class)]
    public function onOrderPlaced(OrderPlaced $event): void
    {
        $this->appender->append(Order::class, $event->id, new OrderPlacedIntegrationEvent(
            orderId: $event->id,
            customerId: $event->customerId,
            lines: $event->lines,
            totalAmountInCents: $event->totalAmountInCents,
            placedAt: $event->placedAt,
        ));
    }

    /**
     * @throws OrderNotFoundException
     */
    #[Subscribe(OrderConfirmed::class)]
    public function onOrderConfirmed(OrderConfirmed $event): void
    {
        $order = $this->orderRepository->load(OrderId::fromString($event->id));

        $this->appender->append(Order::class, $event->id, new OrderConfirmedIntegrationEvent(
            orderId: $event->id,
            customerId: $order->customerId,
            shippingAddress: $this->normalizeAddress($order->shippingAddress),
            confirmedAt: $event->confirmedAt,
        ));
    }

    #[Subscribe(OrderPaymentRequested::class)]
    public function onOrderPaymentRequested(OrderPaymentRequested $event): void
    {
        $this->appender->append(Order::class, $event->orderId, new OrderPaymentRequestedIntegrationEvent(
            orderId: $event->orderId,
            amountInCents: $event->amountInCents,
            reference: $event->reference,
            checkoutUrl: $event->checkoutUrl,
            requestedAt: $event->requestedAt,
        ));
    }

    /**
     * @throws OrderNotFoundException
     */
    #[Subscribe(OrderPaymentCaptured::class)]
    public function onOrderPaymentCaptured(OrderPaymentCaptured $event): void
    {
        $order = $this->orderRepository->load(OrderId::fromString($event->orderId));

        $this->appender->append(Order::class, $event->orderId, new OrderPaymentCapturedIntegrationEvent(
            orderId: $event->orderId,
            customerId: $order->customerId,
            shippingAddress: $this->normalizeAddress($order->shippingAddress),
            capturedAt: $event->capturedAt,
        ));
    }

    #[Subscribe(OrderCancelled::class)]
    public function onOrderCancelled(OrderCancelled $event): void
    {
        $this->appender->append(Order::class, $event->id, new OrderCancelledIntegrationEvent(
            orderId: $event->id,
            cancelledAt: $event->cancelledAt,
        ));
    }

    #[Subscribe(OrderReturnRequested::class)]
    public function onOrderReturnRequested(OrderReturnRequested $event): void
    {
        $this->appender->append(Order::class, $event->id, new OrderReturnRequestedIntegrationEvent(
            orderId: $event->id,
            requestedAt: $event->requestedAt,
        ));
    }

    /**
     * @return array{firstName: string, lastName: string, street: string, postalCode: string, city: string}
     */
    private function normalizeAddress(PostalAddress $address): array
    {
        return [
            'firstName' => $address->fullName->firstName,
            'lastName' => $address->fullName->lastName,
            'street' => $address->address->street,
            'postalCode' => $address->address->postalCode,
            'city' => $address->address->city,
        ];
    }
}
