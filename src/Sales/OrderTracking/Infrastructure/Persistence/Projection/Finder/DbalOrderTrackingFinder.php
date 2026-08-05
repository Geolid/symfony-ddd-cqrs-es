<?php

declare(strict_types=1);

namespace Sales\OrderTracking\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Sales\OrderTracking\Application\Finder\OrderTracking\OrderTrackingFinderInterface;
use Sales\OrderTracking\Application\Finder\OrderTracking\OrderTrackingResult;
use Sales\OrderTracking\Infrastructure\Persistence\Projection\Projector\DbalOrderTrackingProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalFinder;

/**
 * @extends AbstractDbalFinder<OrderTrackingResult>
 *
 * @phpstan-type Row array{order_id: string, customer_id: string, status: string, placed_at: string}
 */
final class DbalOrderTrackingFinder extends AbstractDbalFinder implements OrderTrackingFinderInterface
{
    public function ofOrder(string $orderId): ?OrderTrackingResult
    {
        /** @var Row|false $row */
        $row = $this->query()
            ->andWhere('order_id = :orderId')
            ->setParameter('orderId', $orderId)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $row) {
            return null;
        }

        return $this->mapRow($row);
    }

    public function withCustomer(string $customerId): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($customerId) {
                $qb->andWhere('customer_id = :customerId')
                    ->setParameter('customerId', $customerId);
            },
        );
    }

    public function withStatus(string $status): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($status) {
                $qb->andWhere('status = :status')
                    ->setParameter('status', $status);
            },
        );
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('order_id', 'customer_id', 'status', 'placed_at')
            ->from(DbalOrderTrackingProjector::TABLE)
            ->orderBy('placed_at', 'DESC')
            ->addOrderBy('order_id', 'DESC');
    }

    /**
     * @param Row $row
     */
    protected function mapRow(array $row): OrderTrackingResult
    {
        return new OrderTrackingResult(
            orderId: $row['order_id'],
            customerId: $row['customer_id'],
            status: $row['status'],
            placedAt: new \DateTimeImmutable($row['placed_at'], new \DateTimeZone('UTC')),
        );
    }
}
