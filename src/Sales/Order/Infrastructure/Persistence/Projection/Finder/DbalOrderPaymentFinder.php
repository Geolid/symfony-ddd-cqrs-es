<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Persistence\Projection\Finder;

use Doctrine\DBAL\Connection;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentResult;
use Sales\Order\Infrastructure\Persistence\Projection\Projector\DbalOrderPaymentProjector;

/**
 * @phpstan-type Row array{id: string, order_id: string, amount_in_cents: int, reference: string, status: string, requested_at: string, captured_at: ?string}
 */
final readonly class DbalOrderPaymentFinder implements OrderPaymentFinderInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function ofReference(string $reference): ?OrderPaymentResult
    {
        /** @var Row|false $row */
        $row = $this->connection->fetchAssociative(
            \sprintf('SELECT * FROM %s WHERE reference = :reference', DbalOrderPaymentProjector::TABLE),
            ['reference' => $reference],
        );

        if (false === $row) {
            return null;
        }

        return $this->mapRow($row);
    }

    /**
     * @param Row $row
     */
    private function mapRow(array $row): OrderPaymentResult
    {
        return new OrderPaymentResult(
            id: $row['id'],
            orderId: $row['order_id'],
            amountInCents: (int) $row['amount_in_cents'],
            reference: $row['reference'],
            status: $row['status'],
            requestedAt: new \DateTimeImmutable($row['requested_at'], new \DateTimeZone('UTC')),
            capturedAt: null !== $row['captured_at'] ? new \DateTimeImmutable($row['captured_at'], new \DateTimeZone('UTC')) : null,
        );
    }
}
