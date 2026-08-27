<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Sales\Order\Application\Exception\OrderResultNotFoundException;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Finder\Order\OrderResult;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Order\Infrastructure\Persistence\Projection\Projector\DbalOrderProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalFinder;

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

    public function byCustomer(string $customerId): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($customerId): void {
                $qb->andWhere('customer_id = :customerId')
                    ->setParameter('customerId', $customerId);
            },
        );
    }

    public function closedBefore(\DateTimeImmutable $cutoff): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($cutoff): void {
                $qb->andWhere('closed_at < :cutoff')
                    ->setParameter('cutoff', $cutoff, Types::DATETIME_IMMUTABLE);
            },
        );
    }

    public function deliveredBefore(\DateTimeImmutable $cutoff): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($cutoff): void {
                $qb->andWhere('status = :status')
                    ->andWhere('delivered_at < :cutoff')
                    ->setParameter('status', OrderStatus::DELIVERED->value)
                    ->setParameter('cutoff', $cutoff, Types::DATETIME_IMMUTABLE);
            },
        );
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'customer_id', 'total_amount_in_cents', 'status', 'placed_at', 'confirmed_at', 'dispatched_at', 'delivered_at', 'completed_at', 'return_requested_at', 'returned_at', 'return_rejected_at', 'return_rejection_reason', 'cancelled_at', 'closed_at', 'anonymized_at')
            ->from(DbalOrderProjector::TABLE)
            ->orderBy('id', 'ASC');
    }

    protected function resultClass(): string
    {
        return OrderResult::class;
    }
}
