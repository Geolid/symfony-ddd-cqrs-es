<?php

declare(strict_types=1);

namespace Finance\Payer\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Finance\Payer\Application\Finder\Payer\Exception\PayerResultNotFoundException;
use Finance\Payer\Application\Finder\Payer\PayerFinderInterface;
use Finance\Payer\Application\Finder\Payer\PayerResult;
use Finance\Payer\Infrastructure\Projection\Projector\DbalPayerProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<PayerResult>
 */
final class DbalPayerFinder extends AbstractDbalFinder implements PayerFinderInterface
{
    public function ofId(string $id): PayerResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($id): void {
                $qb->andWhere('id = :id')->setParameter('id', $id);
            },
        )->one() ?? throw PayerResultNotFoundException::forId($id);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'registered_at')
            ->from(DbalPayerProjector::TABLE)
            ->orderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return PayerResult::class;
    }
}
