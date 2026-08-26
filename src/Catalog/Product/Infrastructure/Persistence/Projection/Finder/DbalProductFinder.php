<?php

declare(strict_types=1);

namespace Catalog\Product\Infrastructure\Persistence\Projection\Finder;

use Catalog\Product\Application\Exception\ProductResultNotFoundException;
use Catalog\Product\Application\Finder\Product\ProductFinderInterface;
use Catalog\Product\Application\Finder\Product\ProductResult;
use Catalog\Product\Infrastructure\Persistence\Projection\Projector\DbalProductProjector;
use Doctrine\DBAL\Query\QueryBuilder;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalFinder;

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

    public function sortedByLabel(): static
    {
        return $this->filter(static function (QueryBuilder $qb): void {
            $qb->orderBy('label', 'ASC');
        });
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'label', 'unit_amount_in_cents')
            ->from(DbalProductProjector::TABLE)
            ->orderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return ProductResult::class;
    }
}
