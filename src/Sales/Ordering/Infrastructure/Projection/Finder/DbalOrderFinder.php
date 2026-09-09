<?php

declare(strict_types=1);

namespace Sales\Ordering\Infrastructure\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Sales\Ordering\Application\Finder\Order\Exception\OrderResultNotFoundException;
use Sales\Ordering\Application\Finder\Order\OrderFinderInterface;
use Sales\Ordering\Application\Finder\Order\OrderResult;
use Sales\Ordering\Infrastructure\Projection\Projector\DbalOrderProjector;
use Shared\Infrastructure\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<OrderResult>
 */
final class DbalOrderFinder extends AbstractDbalFinder implements OrderFinderInterface
{
    public function ofId(string $id): OrderResult
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($id): void {
                $qb->andWhere('id = :id')->setParameter('id', $id);
            },
        )->one() ?? throw OrderResultNotFoundException::forId($id);
    }

    public function byBuyer(string $buyerId): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($buyerId): void {
                $qb->andWhere('buyer_id = :buyerId')
                    ->setParameter('buyerId', $buyerId);
            },
        );
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'buyer_id', 'total_amount_in_cents', 'status', 'placed_at', 'confirmed_at', 'prepared_at', 'dispatched_at', 'delivered_at', 'cancelled_at', 'erasure_status')
            ->from(DbalOrderProjector::TABLE)
            ->orderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return OrderResult::class;
    }
}
