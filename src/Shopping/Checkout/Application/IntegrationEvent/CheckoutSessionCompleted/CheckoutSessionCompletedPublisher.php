<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionCompleted;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Domain\CheckoutSession;
use Shopping\Checkout\Domain\Event\CheckoutSessionCompleted;
use Shopping\Checkout\Domain\ValueObject\CheckoutItem;

#[Publisher('shopping.checkout.publish_checkout_session_completed')]
final readonly class CheckoutSessionCompletedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(CheckoutSessionCompleted::class)]
    public function __invoke(CheckoutSessionCompleted $event): void
    {
        $this->publisher->publish(CheckoutSession::class, $event->id, new CheckoutSessionCompletedIntegrationEvent(
            checkoutSessionId: $event->id,
            cartId: $event->cartId,
            customerId: $event->customerId,
            items: array_map($this->toArray(...), $event->items),
            shippingAddress: PostalAddressMapper::toArray($event->shippingAddress),
            billingAddress: PostalAddressMapper::toArray($event->billingAddress),
            totalAmountInCents: $event->totalAmount->cents,
            paymentId: $event->paymentId,
            completedAt: $event->completedAt,
        ));
    }

    /**
     * @return array{productId: string, label: string, unitPriceInCents: int, quantity: int}
     */
    private function toArray(CheckoutItem $item): array
    {
        return [
            'productId' => $item->productId,
            'label' => $item->label->value,
            'unitPriceInCents' => $item->unitPrice->cents,
            'quantity' => $item->quantity->value,
        ];
    }
}
