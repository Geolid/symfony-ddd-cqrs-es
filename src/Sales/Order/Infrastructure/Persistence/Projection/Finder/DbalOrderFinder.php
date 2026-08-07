<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Query\QueryBuilder;
use Sales\Order\Application\Enum\AppOrderStatus;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Finder\Order\OrderResult;
use Sales\Order\Infrastructure\Persistence\Projection\Projector\DbalOrderProjector;
use Shared\Infrastructure\Persistence\Projection\Finder\AbstractDbalCollectionFinder;

/**
 * @extends AbstractDbalCollectionFinder<OrderResult>
 *
 * @phpstan-type Row array{id: string, customer_id: string, total_amount_in_cents: int, status: string, placed_at: string, cancelled_at: string|null}
 */
final class DbalOrderFinder extends AbstractDbalCollectionFinder implements OrderFinderInterface
{
    public function ofId(string $id): ?OrderResult
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

    public function withCustomer(string $customerId): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($customerId) {
                $qb->andWhere('customer_id = :customerId')
                    ->setParameter('customerId', $customerId);
            },
        );
    }

    protected function buildBaseQuery(QueryBuilder $qb): void
    {
        $qb->select('id', 'customer_id', 'total_amount_in_cents', 'status', 'placed_at', 'cancelled_at')
            ->from(DbalOrderProjector::TABLE)
            ->orderBy('placed_at', 'DESC')
            ->addOrderBy('id', 'DESC');
    }

    /**
     * @param Row $row
     */
    protected function mapRow(array $row): OrderResult
    {
        return new OrderResult(
            id: $row['id'],
            customerId: $row['customer_id'],
            totalAmountInCents: (int) $row['total_amount_in_cents'],
            status: AppOrderStatus::from($row['status']),
            placedAt: new \DateTimeImmutable($row['placed_at'], new \DateTimeZone('UTC')),
            cancelledAt: null !== $row['cancelled_at'] ? new \DateTimeImmutable($row['cancelled_at'], new \DateTimeZone('UTC')) : null,
        );
    }
}
