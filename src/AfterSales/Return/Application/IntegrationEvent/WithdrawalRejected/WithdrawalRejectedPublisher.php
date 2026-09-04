<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\IntegrationEvent\WithdrawalRejected;

use AfterSales\Return\Domain\Event\WithdrawalRejected;
use AfterSales\Return\Domain\Exception\WithdrawalNotFoundException;
use AfterSales\Return\Domain\Repository\WithdrawalRepositoryInterface;
use AfterSales\Return\Domain\ValueObject\WithdrawalId;
use AfterSales\Return\Domain\Withdrawal;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('after_sales.return.publish_withdrawal_rejected')]
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
