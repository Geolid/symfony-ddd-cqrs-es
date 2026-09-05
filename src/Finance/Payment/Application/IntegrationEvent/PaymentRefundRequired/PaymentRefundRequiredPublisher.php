<?php

declare(strict_types=1);

namespace Finance\Payment\Application\IntegrationEvent\PaymentRefundRequired;

use Finance\Payment\Domain\Event\PaymentRefundRequired;
use Finance\Payment\Domain\Payment;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('finance.payment.publish_payment_refund_required')]
final readonly class PaymentRefundRequiredPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(PaymentRefundRequired::class)]
    public function __invoke(PaymentRefundRequired $event): void
    {
        $this->publisher->publish(Payment::class, $event->id, new PaymentRefundRequiredIntegrationEvent(
            orderId: $event->orderId,
            requiredAt: $event->requiredAt,
        ));
    }
}
