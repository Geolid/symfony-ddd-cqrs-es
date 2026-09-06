<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\IntegrationEvent\WithdrawalRequested;

use AfterSales\Return\Domain\Event\WithdrawalRequested;
use AfterSales\Return\Domain\Withdrawal;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('after_sales.return.publish_withdrawal_requested')]
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
            buyerId: $event->buyerId,
            shippingAddress: $event->shippingAddress->toArray(),
            requestedAt: $event->requestedAt,
        ));
    }
}
