<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionCompleted;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Application\Mapper\CheckoutItemMapper;
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
            items: array_map(
                static fn (CheckoutItem $item): array => [
                    ...CheckoutItemMapper::toArray($item),
                    'taxAmountInCents' => $item->taxedTotal()->taxAmount->cents,
                ],
                $event->items,
            ),
            currency: $event->total->excludingTax->currency->value,
            shippingAddress: PostalAddressMapper::toArray($event->shippingAddress),
            billingAddress: PostalAddressMapper::toArray($event->billingAddress),
            paymentId: $event->paymentId,
            completedAt: $event->completedAt,
        ));
    }
}
