<?php

declare(strict_types=1);

namespace Finance\Payment\Application\IntegrationEvent\PaymentRefundConfirmed;

use Finance\Payment\Domain\Event\PaymentRefundConfirmed;
use Finance\Payment\Domain\Payment;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('finance.payment.publish_payment_refund_confirmed')]
final readonly class PaymentRefundConfirmedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(PaymentRefundConfirmed::class)]
    public function __invoke(PaymentRefundConfirmed $event): void
    {
        $this->publisher->publish(Payment::class, $event->id, new PaymentRefundConfirmedIntegrationEvent(
            orderId: $event->orderId,
            refundId: $event->refundId,
            confirmedAt: $event->confirmedAt,
        ));
    }
}
