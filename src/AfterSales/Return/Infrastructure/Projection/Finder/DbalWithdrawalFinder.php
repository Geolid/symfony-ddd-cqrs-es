<?php

declare(strict_types=1);

namespace AfterSales\Return\Infrastructure\Projection\Finder;

use AfterSales\Return\Application\Finder\Withdrawal\WithdrawalFinderInterface;
use AfterSales\Return\Application\Finder\Withdrawal\WithdrawalResult;
use AfterSales\Return\Application\WithdrawalStatus;
use AfterSales\Return\Infrastructure\Projection\Projector\DbalWithdrawalProjector;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<WithdrawalResult>
 */
final class DbalWithdrawalFinder extends AbstractDbalFinder implements WithdrawalFinderInterface
{
    public function byOrder(string $orderId): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($orderId): void {
                $qb->andWhere('order_id = :orderId')->setParameter('orderId', $orderId);
            },
        );
    }

    public function byOrders(string ...$orderIds): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($orderIds): void {
                $qb->andWhere('order_id IN (:orderIds)')
                    ->setParameter('orderIds', $orderIds, ArrayParameterType::STRING);
            },
        );
    }

    public function active(): static
    {
        return $this->filter(
            static function (QueryBuilder $qb): void {
                $qb->andWhere('status NOT IN (:terminalStatuses)')
                    ->setParameter(
                        'terminalStatuses',
                        [WithdrawalStatus::APPROVED->value, WithdrawalStatus::REJECTED->value],
                        ArrayParameterType::STRING,
                    );
            },
        );
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'order_id', 'buyer_id', 'status', 'requested_at', 'received_at', 'approved_at', 'rejected_at')
            ->from(DbalWithdrawalProjector::TABLE)
            ->orderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return WithdrawalResult::class;
    }
}
