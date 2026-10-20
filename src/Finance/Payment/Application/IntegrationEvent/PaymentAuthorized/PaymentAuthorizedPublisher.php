<?php

declare(strict_types=1);

namespace Finance\Payment\Application\IntegrationEvent\PaymentAuthorized;

use Finance\Payment\Domain\Event\PaymentAuthorized;
use Finance\Payment\Domain\Payment;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Message\Message;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('finance.payment.publish_payment_authorized')]
final readonly class PaymentAuthorizedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(PaymentAuthorized::class)]
    public function __invoke(PaymentAuthorized $event, Message $message): void
    {
        $this->publisher->publish(Payment::class, $event->id->toString(), new PaymentAuthorizedIntegrationEvent(
            paymentId: $event->id->toString(),
            checkoutSessionId: $event->checkoutSessionId,
            authorizedAt: $event->authorizedAt,
        ), $message);
    }
}
