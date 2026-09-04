<?php

declare(strict_types=1);

namespace Sales\Buyer\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Sales\Buyer\Application\Exception\BuyerResultNotFoundException;
use Sales\Buyer\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Buyer\Application\Finder\Buyer\BuyerResult;
use Sales\Buyer\Infrastructure\Projection\Projector\DbalBuyerProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<BuyerResult>
 */
final class DbalBuyerFinder extends AbstractDbalFinder implements BuyerFinderInterface
{
    public function ofId(string $id): BuyerResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($id): void {
                $qb->andWhere('id = :id')->setParameter('id', $id);
            },
        )->one() ?? throw BuyerResultNotFoundException::forId($id);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'email', 'registered_at')
            ->from(DbalBuyerProjector::TABLE)
            ->orderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return BuyerResult::class;
    }
}
