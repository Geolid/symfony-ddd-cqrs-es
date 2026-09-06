<?php

declare(strict_types=1);

namespace Finance\Payment\Application\IntegrationEvent\PaymentRefundFailed;

use Finance\Payment\Domain\Event\PaymentRefundFailed;
use Finance\Payment\Domain\Payment;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('finance.payment.publish_payment_refund_failed')]
final readonly class PaymentRefundFailedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(PaymentRefundFailed::class)]
    public function __invoke(PaymentRefundFailed $event): void
    {
        $this->publisher->publish(Payment::class, $event->id, new PaymentRefundFailedIntegrationEvent(
            orderId: $event->orderId,
            refundId: $event->refundId,
            failedAt: $event->failedAt,
        ));
    }
}
