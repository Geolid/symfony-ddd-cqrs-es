<?php

declare(strict_types=1);

namespace Finance\Payment\Application\IntegrationEvent\PaymentRequested;

use Finance\Payment\Domain\Event\PaymentRequested;
use Finance\Payment\Domain\Payment;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('finance.payment.publish_payment_requested')]
final readonly class PaymentRequestedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(PaymentRequested::class)]
    public function __invoke(PaymentRequested $event): void
    {
        $this->publisher->publish(Payment::class, $event->id, new PaymentRequestedIntegrationEvent(
            paymentId: $event->id,
            orderId: $event->orderId,
            amountInCents: $event->amountInCents,
            reference: $event->reference,
            checkoutUrl: $event->checkoutUrl,
            requestedAt: $event->requestedAt,
        ));
    }
}
