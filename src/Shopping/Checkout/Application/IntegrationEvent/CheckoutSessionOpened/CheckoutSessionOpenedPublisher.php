<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionOpened;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Domain\CheckoutSession\CheckoutSession;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionOpened;

#[Publisher('shopping.checkout.publish_checkout_session_opened')]
final readonly class CheckoutSessionOpenedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(CheckoutSessionOpened::class)]
    public function __invoke(CheckoutSessionOpened $event): void
    {
        $this->publisher->publish(CheckoutSession::class, $event->id, new CheckoutSessionOpenedIntegrationEvent(
            checkoutSessionId: $event->id,
            cartId: $event->cartId,
            shopperId: $event->shopperId,
            lines: $event->lines,
            shippingAddress: PostalAddressMapper::toArray($event->shippingAddress),
            billingAddress: PostalAddressMapper::toArray($event->billingAddress),
            totalAmountInCents: $event->totalAmountInCents,
            openedAt: $event->openedAt,
        ));
    }
}
