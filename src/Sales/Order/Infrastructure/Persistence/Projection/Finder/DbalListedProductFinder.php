<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Sales\Order\Application\Finder\ListedProduct\ListedProductFinderInterface;
use Sales\Order\Application\Finder\ListedProduct\ListedProductResult;
use Sales\Order\Infrastructure\Persistence\Projection\Projector\DbalListedProductProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalCollectionFinder;

/**
 * @extends AbstractDbalCollectionFinder<ListedProductResult>
 *
 * @phpstan-type Row array{product_id: string, label: string, unit_amount_in_cents: int}
 */
final class DbalListedProductFinder extends AbstractDbalCollectionFinder implements ListedProductFinderInterface
{
    public function byIds(string ...$productIds): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($productIds): void {
                $qb->andWhere('product_id IN (:productIds)')
                    ->setParameter('productIds', $productIds, ArrayParameterType::STRING);
            },
        );
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('product_id', 'label', 'unit_amount_in_cents')
            ->from(DbalListedProductProjector::TABLE);
    }

    /**
     * @param Row $row
     */
    protected function mapRow(array $row): ListedProductResult
    {
        return new ListedProductResult(
            productId: $row['product_id'],
            label: $row['label'],
            unitAmountInCents: $row['unit_amount_in_cents'],
        );
    }
}
