<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Projection\Finder;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Sales\Order\Application\Finder\ListedProduct\ListedProductFinderInterface;
use Sales\Order\Application\Finder\ListedProduct\ListedProductResult;
use Sales\Order\Infrastructure\Projection\Projector\DbalListedProductProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<ListedProductResult>
 */
final class DbalListedProductFinder extends AbstractDbalFinder implements ListedProductFinderInterface
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
            ->from(DbalListedProductProjector::TABLE)
            ->orderBy('product_id', 'ASC');
    }

    protected function resultClass(): string
    {
        return ListedProductResult::class;
    }
}
