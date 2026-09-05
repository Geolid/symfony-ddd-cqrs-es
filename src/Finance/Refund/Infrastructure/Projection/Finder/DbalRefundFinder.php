<?php

declare(strict_types=1);

namespace Finance\Refund\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Finance\Refund\Application\Finder\Refund\Exception\RefundResultNotFoundException;
use Finance\Refund\Application\Finder\Refund\RefundFinderInterface;
use Finance\Refund\Application\Finder\Refund\RefundResult;
use Finance\Refund\Infrastructure\Projection\Projector\DbalRefundProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<RefundResult>
 */
final class DbalRefundFinder extends AbstractDbalFinder implements RefundFinderInterface
{
    public function ofId(string $id): RefundResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($id): void {
                $qb->andWhere('id = :id')->setParameter('id', $id);
            },
        )->one() ?? throw RefundResultNotFoundException::forId($id);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'payment_id', 'order_id', 'amount_in_cents', 'status', 'initiated_at', 'refunded_at', 'failed_at')
            ->from(DbalRefundProjector::TABLE)
            ->orderBy('initiated_at', 'ASC')
            ->addOrderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return RefundResult::class;
    }
}
