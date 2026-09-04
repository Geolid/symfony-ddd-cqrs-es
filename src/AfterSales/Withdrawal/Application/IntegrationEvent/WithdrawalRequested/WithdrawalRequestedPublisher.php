<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Application\IntegrationEvent\WithdrawalRequested;

use AfterSales\Withdrawal\Domain\Event\WithdrawalRequested;
use AfterSales\Withdrawal\Domain\Withdrawal;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('after_sales.withdrawal.publish_withdrawal_requested')]
final readonly class WithdrawalRequestedPublisher
{
    public function __construct(private IntegrationEventPublisherInterface $publisher)
    {
    }

    #[Subscribe(WithdrawalRequested::class)]
    public function __invoke(WithdrawalRequested $event): void
    {
        $this->publisher->publish(Withdrawal::class, $event->id, new WithdrawalRequestedIntegrationEvent(
            withdrawalId: $event->id,
            orderId: $event->orderId,
            customerId: $event->customerId,
            shippingAddress: $event->shippingAddress,
            requestedAt: $event->requestedAt,
        ));
    }
}
