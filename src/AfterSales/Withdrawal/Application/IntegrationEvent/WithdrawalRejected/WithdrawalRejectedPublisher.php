<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Application\IntegrationEvent\WithdrawalRejected;

use AfterSales\Withdrawal\Domain\Event\WithdrawalRejected;
use AfterSales\Withdrawal\Domain\Exception\WithdrawalNotFoundException;
use AfterSales\Withdrawal\Domain\Repository\WithdrawalRepositoryInterface;
use AfterSales\Withdrawal\Domain\ValueObject\WithdrawalId;
use AfterSales\Withdrawal\Domain\Withdrawal;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('after_sales.withdrawal.publish_withdrawal_rejected')]
final readonly class WithdrawalRejectedPublisher
{
    public function __construct(
        private IntegrationEventPublisherInterface $publisher,
        private WithdrawalRepositoryInterface $repository,
    ) {
    }

    /**
     * @throws WithdrawalNotFoundException
     */
    #[Subscribe(WithdrawalRejected::class)]
    public function __invoke(WithdrawalRejected $event): void
    {
        $this->publisher->publish(Withdrawal::class, $event->id, new WithdrawalRejectedIntegrationEvent(
            withdrawalId: $event->id,
            orderId: $this->repository->load(WithdrawalId::fromString($event->id))->orderId,
            reason: $event->reason,
            rejectedAt: $event->rejectedAt,
        ));
    }
}
