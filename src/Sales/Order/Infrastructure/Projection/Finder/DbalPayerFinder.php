<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Sales\Order\Application\Finder\Payer\PayerFinderInterface;
use Sales\Order\Application\Finder\Payer\PayerResult;
use Sales\Order\Infrastructure\Projection\Projector\DbalPayerProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<PayerResult>
 */
final class DbalPayerFinder extends AbstractDbalFinder implements PayerFinderInterface
{
    public function ofIdOrNull(string $payerId): ?PayerResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($payerId): void {
                $qb->andWhere('payer_id = :payerId')->setParameter('payerId', $payerId);
            },
        )->one();
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('payer_id', 'address')
            ->from(DbalPayerProjector::TABLE)
            ->orderBy('payer_id', 'ASC');
    }

    protected function resultClass(): string
    {
        return PayerResult::class;
    }
}
