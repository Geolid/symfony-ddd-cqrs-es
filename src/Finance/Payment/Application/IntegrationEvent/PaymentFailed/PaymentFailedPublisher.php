<?php

declare(strict_types=1);

namespace Finance\Payment\Application\IntegrationEvent\PaymentFailed;

use Finance\Payment\Domain\Event\PaymentFailed;
use Finance\Payment\Domain\Payment;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('finance.payment.publish_payment_failed')]
final readonly class PaymentFailedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(PaymentFailed::class)]
    public function __invoke(PaymentFailed $event): void
    {
        $this->publisher->publish(Payment::class, $event->id, new PaymentFailedIntegrationEvent(
            orderId: $event->orderId,
            failedAt: $event->failedAt,
        ));
    }
}
