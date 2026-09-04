<?php

declare(strict_types=1);

namespace Finance\Payment\Application\IntegrationEvent\PaymentCaptured;

use Finance\Payment\Domain\Event\PaymentCaptured;
use Finance\Payment\Domain\Payment;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('finance.payment.publish_payment_captured')]
final readonly class PaymentCapturedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(PaymentCaptured::class)]
    public function __invoke(PaymentCaptured $event): void
    {
        $this->publisher->publish(Payment::class, $event->id, new PaymentCapturedIntegrationEvent(
            orderId: $event->orderId,
            capturedAt: $event->capturedAt,
        ));
    }
}
