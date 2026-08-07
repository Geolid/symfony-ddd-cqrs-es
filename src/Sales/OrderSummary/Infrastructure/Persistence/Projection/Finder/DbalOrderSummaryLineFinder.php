<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Sales\OrderSummary\Application\Finder\OrderSummaryLine\OrderSummaryLineFinderInterface;
use Sales\OrderSummary\Application\Finder\OrderSummaryLine\OrderSummaryLineResult;
use Sales\OrderSummary\Infrastructure\Persistence\Projection\Projector\DbalOrderSummaryLineProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalCollectionFinder;

/**
 * @extends AbstractDbalCollectionFinder<OrderSummaryLineResult>
 *
 * @phpstan-type Row array{order_id: string, label: string, quantity: int|string, unit_amount_in_cents: int|string}
 */
final class DbalOrderSummaryLineFinder extends AbstractDbalCollectionFinder implements OrderSummaryLineFinderInterface
{
    public function withOrder(string $orderId): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($orderId) {
                $qb->andWhere('order_id = :orderId')
                    ->setParameter('orderId', $orderId);
            },
        );
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('order_id', 'label', 'quantity', 'unit_amount_in_cents')
            ->from(DbalOrderSummaryLineProjector::TABLE)
            ->orderBy('position', 'ASC');
    }

    /**
     * @param Row $row
     */
    protected function mapRow(array $row): OrderSummaryLineResult
    {
        return new OrderSummaryLineResult(
            orderId: $row['order_id'],
            label: $row['label'],
            quantity: (int) $row['quantity'],
            unitAmountInCents: (int) $row['unit_amount_in_cents'],
        );
    }
}
