<?php

declare(strict_types=1);

namespace Catalog\Listing\Infrastructure\Projection\Finder;

use Catalog\Listing\Application\Finder\Product\Exception\ProductResultNotFoundException;
use Catalog\Listing\Application\Finder\Product\ProductFinderInterface;
use Catalog\Listing\Application\Finder\Product\ProductResult;
use Catalog\Listing\Infrastructure\Projection\Projector\DbalProductProjector;
use Doctrine\DBAL\Query\QueryBuilder;
use Shared\Application\Finder\SortDirection;
use Shared\Infrastructure\Projection\Finder\AbstractPaginatableDbalFinder;

/**
 * @extends AbstractPaginatableDbalFinder<ProductResult>
 */
final class DbalProductFinder extends AbstractPaginatableDbalFinder implements ProductFinderInterface
{
    public function ofId(string $id): ProductResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($id): void {
                $qb->andWhere('id = :id')->setParameter('id', $id);
            },
        )->one() ?? throw ProductResultNotFoundException::forId($id);
    }

    protected function configureBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'label', 'unit_price_in_cents', 'listed_at', 'repriced_at')
            ->from(DbalProductProjector::TABLE);
    }

    protected function defaultSort(): array
    {
        return ['listed_at' => SortDirection::Ascending, 'id' => SortDirection::Ascending];
    }

    protected function resultClass(): string
    {
        return ProductResult::class;
    }
}
