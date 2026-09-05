<?php

declare(strict_types=1);

namespace Finance\Payment\Application\IntegrationEvent\PaymentRefundRejected;

use Finance\Payment\Domain\Event\PaymentRefundRejected;
use Finance\Payment\Domain\Payment;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('finance.payment.publish_payment_refund_rejected')]
final readonly class PaymentRefundRejectedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(PaymentRefundRejected::class)]
    public function __invoke(PaymentRefundRejected $event): void
    {
        $this->publisher->publish(Payment::class, $event->id, new PaymentRefundRejectedIntegrationEvent(
            orderId: $event->orderId,
            rejectedAt: $event->rejectedAt,
        ));
    }
}
