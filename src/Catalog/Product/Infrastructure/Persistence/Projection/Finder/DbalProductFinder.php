<?php

declare(strict_types=1);

namespace Catalog\Product\Infrastructure\Persistence\Projection\Finder;

use Catalog\Product\Application\Finder\Product\ProductFinderInterface;
use Catalog\Product\Application\Finder\Product\ProductResult;
use Catalog\Product\Infrastructure\Persistence\Projection\Projector\DbalProductProjector;
use Doctrine\DBAL\Query\QueryBuilder;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<ProductResult>
 *
 * @phpstan-type Row array{id: string, label: string, unit_amount_in_cents: int|string}
 */
final class DbalProductFinder extends AbstractDbalFinder implements ProductFinderInterface
{
    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'label', 'unit_amount_in_cents')
            ->from(DbalProductProjector::TABLE);
    }

    /**
     * @param Row $row
     */
    protected function mapRow(array $row): ProductResult
    {
        return new ProductResult(
            id: $row['id'],
            label: $row['label'],
            unitAmountInCents: (int) $row['unit_amount_in_cents'],
        );
    }
}
