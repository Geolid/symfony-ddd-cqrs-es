<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Connection;
use Sales\Order\Application\Finder\OrderLine\OrderLineFinderInterface;
use Sales\Order\Application\Finder\OrderLine\OrderLineResult;
use Sales\Order\Infrastructure\Persistence\Projection\Projector\DbalOrderLineProjector;

/**
 * @phpstan-type Row array{order_id: string, position: int, label: string, quantity: int, unit_amount_in_cents: int}
 */
final readonly class DbalOrderLineFinder implements OrderLineFinderInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function allForOrder(string $orderId): array
    {
        /** @var list<Row> $rows */
        $rows = $this->connection->fetchAllAssociative(
            \sprintf('SELECT * FROM %s WHERE order_id = :orderId ORDER BY position ASC', DbalOrderLineProjector::TABLE),
            ['orderId' => $orderId],
        );

        return array_map($this->mapRow(...), $rows);
    }

    /**
     * @param Row $row
     */
    private function mapRow(array $row): OrderLineResult
    {
        return new OrderLineResult(
            orderId: $row['order_id'],
            label: $row['label'],
            quantity: (int) $row['quantity'],
            unitAmountInCents: (int) $row['unit_amount_in_cents'],
        );
    }
}
