<?php

declare(strict_types=1);

namespace Catalog\Listing\Infrastructure\Projection\Finder;

use Catalog\Listing\Application\Exception\ProductResultNotFoundException;
use Catalog\Listing\Application\Finder\Product\ProductFinderInterface;
use Catalog\Listing\Application\Finder\Product\ProductResult;
use Catalog\Listing\Infrastructure\Projection\Projector\DbalProductProjector;
use Doctrine\DBAL\Query\QueryBuilder;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<ProductResult>
 */
final class DbalProductFinder extends AbstractDbalFinder implements ProductFinderInterface
{
    public function ofId(string $id): ProductResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($id): void {
                $qb->andWhere('id = :id')->setParameter('id', $id);
            },
        )->one() ?? throw ProductResultNotFoundException::forId($id);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'label', 'unit_price_in_cents', 'listed_at', 'repriced_at')
            ->from(DbalProductProjector::TABLE)
            ->orderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return ProductResult::class;
    }
}
