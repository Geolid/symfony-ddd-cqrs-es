<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Sales\OrderSummary\Application\Finder\OrderSummaryLine\OrderSummaryLineFinderInterface;
use Sales\OrderSummary\Application\Finder\OrderSummaryLine\OrderSummaryLineResult;
use Sales\OrderSummary\Infrastructure\Projection\Projector\DbalOrderSummaryLineProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<OrderSummaryLineResult>
 */
final class DbalOrderSummaryLineFinder extends AbstractDbalFinder implements OrderSummaryLineFinderInterface
{
    public function byOrder(string $orderId): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($orderId): void {
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

    protected function resultClass(): string
    {
        return OrderSummaryLineResult::class;
    }
}
