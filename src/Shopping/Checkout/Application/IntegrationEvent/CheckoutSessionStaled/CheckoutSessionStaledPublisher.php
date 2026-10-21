<?php

declare(strict_types=1);

namespace Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionStaled;

use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;
use Shopping\Checkout\Domain\Event\CheckoutSessionStaled;

#[Publisher('shopping.checkout.publish_checkout_session_staled')]
final readonly class CheckoutSessionStaledPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(CheckoutSessionStaled::class)]
    public function __invoke(CheckoutSessionStaled $event, Message $message): void
    {
        $this->publisher->publish(new CheckoutSessionStaledIntegrationEvent(
            checkoutSessionId: $event->id,
            staledAt: $event->staledAt,
        ), $message);
    }
}
