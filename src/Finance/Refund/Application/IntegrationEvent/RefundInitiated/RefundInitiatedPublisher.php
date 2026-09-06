<?php

declare(strict_types=1);

namespace Finance\Refund\Application\IntegrationEvent\RefundInitiated;

use Finance\Refund\Domain\Event\RefundInitiated;
use Finance\Refund\Domain\Refund;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('finance.refund.publish_refund_initiated')]
final readonly class RefundInitiatedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(RefundInitiated::class)]
    public function __invoke(RefundInitiated $event): void
    {
        $this->publisher->publish(Refund::class, $event->id, new RefundInitiatedIntegrationEvent(
            refundId: $event->id,
            paymentId: $event->paymentId,
            orderId: $event->orderId,
            amountInCents: $event->amount->cents,
            initiatedAt: $event->initiatedAt,
        ));
    }
}
