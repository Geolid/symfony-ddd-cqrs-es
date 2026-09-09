<?php

declare(strict_types=1);

namespace Finance\Payment\Application\IntegrationEvent\PaymentAbandoned;

use Finance\Payment\Domain\Event\PaymentAbandoned;
use Finance\Payment\Domain\Payment;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('finance.payment.publish_payment_abandoned')]
final readonly class PaymentAbandonedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(PaymentAbandoned::class)]
    public function __invoke(PaymentAbandoned $event): void
    {
        $this->publisher->publish(Payment::class, $event->id, new PaymentAbandonedIntegrationEvent(
            paymentId: $event->id,
            cartId: $event->cartId,
            abandonedAt: $event->abandonedAt,
        ));
    }
}
