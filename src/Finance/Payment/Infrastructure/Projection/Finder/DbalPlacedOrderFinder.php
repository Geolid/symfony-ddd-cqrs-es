<?php

declare(strict_types=1);

namespace Finance\Payment\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Finance\Payment\Application\Finder\PlacedOrder\Exception\PlacedOrderResultNotFoundException;
use Finance\Payment\Application\Finder\PlacedOrder\PlacedOrderFinderInterface;
use Finance\Payment\Application\Finder\PlacedOrder\PlacedOrderResult;
use Finance\Payment\Infrastructure\Projection\Projector\DbalPlacedOrderProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<PlacedOrderResult>
 */
final class DbalPlacedOrderFinder extends AbstractDbalFinder implements PlacedOrderFinderInterface
{
    public function ofId(string $orderId): PlacedOrderResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($orderId): void {
                $qb->andWhere('order_id = :orderId')->setParameter('orderId', $orderId);
            },
        )->one() ?? throw PlacedOrderResultNotFoundException::forId($orderId);
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('order_id', 'amount_in_cents', 'billing_address', 'cancelled')
            ->from(DbalPlacedOrderProjector::TABLE)
            ->orderBy('order_id', 'ASC');
    }

    protected function resultClass(): string
    {
        return PlacedOrderResult::class;
    }
}
