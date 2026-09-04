<?php

declare(strict_types=1);

namespace AfterSales\Return\Application\IntegrationEvent\WithdrawalApproved;

use AfterSales\Return\Domain\Event\WithdrawalApproved;
use AfterSales\Return\Domain\Exception\WithdrawalNotFoundException;
use AfterSales\Return\Domain\Repository\WithdrawalRepositoryInterface;
use AfterSales\Return\Domain\ValueObject\WithdrawalId;
use AfterSales\Return\Domain\Withdrawal;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Shared\Application\IntegrationEvent\IntegrationEventPublisherInterface;
use Shared\Application\IntegrationEvent\Publisher;

#[Publisher('after_sales.return.publish_withdrawal_approved')]
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
