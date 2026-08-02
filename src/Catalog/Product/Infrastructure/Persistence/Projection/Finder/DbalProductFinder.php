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
 * @phpstan-type Row array{id: string, label: string, unit_amount_in_cents: int|string, delisted: string|int}
 */
final class DbalProductFinder extends AbstractDbalFinder implements ProductFinderInterface
{
    public function getById(string $id): ?ProductResult
    {
        /** @var Row|false $row */
        $row = $this->query()
            ->andWhere('id = :id')
            ->setParameter('id', $id)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $row) {
            return null;
        }

        return $this->mapRow($row);
    }

    public function withoutDelisted(): static
    {
        return $this->filter(static function (QueryBuilder $qb): void {
            $qb->andWhere('delisted = 0');
        });
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'label', 'unit_amount_in_cents', 'delisted')
            ->from(DbalProductProjector::TABLE)
            ->orderBy('label', 'ASC')
            ->addOrderBy('id', 'ASC');
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
            delisted: (bool) $row['delisted'],
        );
    }
}
