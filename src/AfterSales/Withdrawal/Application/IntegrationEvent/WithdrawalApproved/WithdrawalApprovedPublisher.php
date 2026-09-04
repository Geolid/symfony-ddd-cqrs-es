<?php

declare(strict_types=1);

namespace AfterSales\Withdrawal\Application\IntegrationEvent\WithdrawalApproved;

use AfterSales\Withdrawal\Domain\Event\WithdrawalApproved;
use AfterSales\Withdrawal\Domain\Exception\WithdrawalNotFoundException;
use AfterSales\Withdrawal\Domain\Repository\WithdrawalRepositoryInterface;
use AfterSales\Withdrawal\Domain\ValueObject\WithdrawalId;
use AfterSales\Withdrawal\Domain\Withdrawal;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('after_sales.withdrawal.publish_withdrawal_approved')]
final readonly class WithdrawalApprovedPublisher
{
    public function __construct(
        private IntegrationEventPublisherInterface $publisher,
        private WithdrawalRepositoryInterface $repository,
    ) {
    }

    /**
     * @throws WithdrawalNotFoundException
     */
    #[Subscribe(WithdrawalApproved::class)]
    public function __invoke(WithdrawalApproved $event): void
    {
        $this->publisher->publish(Withdrawal::class, $event->id, new WithdrawalApprovedIntegrationEvent(
            withdrawalId: $event->id,
            orderId: $this->repository->load(WithdrawalId::fromString($event->id))->orderId,
            approvedAt: $event->approvedAt,
        ));
    }
}
