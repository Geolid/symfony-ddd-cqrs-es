<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\EventStore\Translator;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Store\Store;
use Sales\Order\Application\Event\OrderCancelledIntegrationEvent;
use Sales\Order\Application\Event\OrderPaymentCapturedIntegrationEvent;
use Sales\Order\Application\Event\OrderPaymentRequestedIntegrationEvent;
use Sales\Order\Application\Event\OrderPlacedIntegrationEvent;
use Sales\Order\Domain\Event\OrderCancelled;
use Sales\Order\Domain\Event\OrderPaymentCaptured;
use Sales\Order\Domain\Event\OrderPaymentRequested;
use Sales\Order\Domain\Event\OrderPlaced;
use Sales\Order\Domain\Repository\OrderRepositoryInterface;
use Sales\Order\Domain\ValueObject\OrderId;
use Shared\Domain\Exception\AggregateNotFoundException;
use Shared\Infrastructure\Persistence\EventStore\IntegrationStreamId;
use Shared\Infrastructure\Persistence\EventStore\Translator\AbstractIntegrationEventTranslator;
use Shared\Infrastructure\Persistence\EventStore\Translator\Translator;

#[Translator('sales.order.integration')]
final readonly class OrderIntegrationEventTranslator extends AbstractIntegrationEventTranslator
{
    public function __construct(
        Store $store,
        private OrderRepositoryInterface $orderRepository,
    ) {
        parent::__construct($store);
    }

    #[Subscribe(OrderPaymentRequested::class)]
    public function onOrderPaymentRequested(OrderPaymentRequested $event): void
    {
        $this->append(
            IntegrationStreamId::build('sales.order', $event->orderId),
            new OrderPaymentRequestedIntegrationEvent(
                orderId: $event->orderId,
                amountInCents: $event->amountInCents,
                reference: $event->reference,
                checkoutUrl: $event->checkoutUrl,
                requestedAt: $event->requestedAt,
            ),
        );
    }

    #[Subscribe(OrderPlaced::class)]
    public function onOrderPlaced(OrderPlaced $event): void
    {
        $this->append(
            IntegrationStreamId::build('sales.order', $event->id),
            new OrderPlacedIntegrationEvent(
                orderId: $event->id,
                customerId: $event->customerId,
                lines: $event->lines,
                totalAmountInCents: $event->totalAmountInCents,
                placedAt: $event->placedAt,
            ),
        );
    }

    #[Subscribe(OrderCancelled::class)]
    public function onOrderCancelled(OrderCancelled $event): void
    {
        $this->append(
            IntegrationStreamId::build('sales.order', $event->id),
            new OrderCancelledIntegrationEvent(
                orderId: $event->id,
                cancelledAt: $event->cancelledAt,
            ),
        );
    }

    /**
     * @throws AggregateNotFoundException
     */
    #[Subscribe(OrderPaymentCaptured::class)]
    public function onOrderPaymentCaptured(OrderPaymentCaptured $event): void
    {
        $order = $this->orderRepository->load(OrderId::fromString($event->orderId));
        $shippingAddress = $order->shippingAddress();

        $this->append(
            IntegrationStreamId::build('sales.order', $event->orderId),
            new OrderPaymentCapturedIntegrationEvent(
                orderId: $event->orderId,
                customerId: $order->customerId(),
                shippingAddress: [
                    'firstName' => $shippingAddress->fullName->firstName,
                    'lastName' => $shippingAddress->fullName->lastName,
                    'street' => $shippingAddress->address->street,
                    'postalCode' => $shippingAddress->address->postalCode,
                    'city' => $shippingAddress->address->city,
                ],
                capturedAt: $event->capturedAt,
            ),
        );
    }
}
