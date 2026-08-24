<?php

declare(strict_types=1);

namespace Sales\Order\Application\IntegrationEvent\OrderConfirmed;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Sales\Order\Domain\Event\OrderConfirmed;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Order;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;
use Shared\Domain\ValueObject\PostalAddress;

#[Publisher('sales.order.order_confirmed_publisher')]
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
        $order = $this->orderRepository->load(OrderId::fromString($event->id));

        $this->publisher->publish(Order::class, $event->id, new OrderConfirmedIntegrationEvent(
            orderId: $event->id,
            customerId: $order->customerId,
            shippingAddress: $this->normalizeAddress($order->shippingAddress),
            confirmedAt: $event->confirmedAt,
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
