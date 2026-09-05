<?php

declare(strict_types=1);

namespace Finance\Tests\Refund\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use Finance\Refund\Application\RefundStatus;
use Finance\Refund\Infrastructure\Projection\Projector\DbalRefundProjector;
use Finance\Tests\Refund\Support\Builder\RefundBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{payment_id: string, order_id: string, amount_in_cents: int|string, status: string, initiated_at: string, refunded_at: ?string, failed_at: ?string}
 */
final class DbalRefundProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnRefundInitiated(): void
    {
        // Given
        $builder = RefundBuilder::new();
        $refund = $builder->create();

        // When
        $this->store($refund);

        // Then
        $row = $this->fetchRow($refund->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['paymentId'], $row['payment_id']);
        self::assertSame($builder['orderId'], $row['order_id']);
        self::assertSame($builder['amount']->cents, (int) $row['amount_in_cents']);
        self::assertSame(RefundStatus::INITIATED->value, $row['status']);
        self::assertNull($row['refunded_at']);
        self::assertNull($row['failed_at']);
    }

    #[Test]
    public function itProjectsOnRefundConfirmed(): void
    {
        // Given
        $other = RefundBuilder::new()->create();
        $this->store($other);
        $refund = RefundBuilder::new()->confirmed()->create();

        // When
        $this->store($refund);

        // Then
        $row = $this->fetchRow($refund->id->toString());
        self::assertNotFalse($row);
        self::assertSame(RefundStatus::REFUNDED->value, $row['status']);
        self::assertNotNull($row['refunded_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(RefundStatus::INITIATED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnRefundFailed(): void
    {
        // Given
        $other = RefundBuilder::new()->create();
        $this->store($other);
        $refund = RefundBuilder::new()->failed()->create();

        // When
        $this->store($refund);

        // Then
        $row = $this->fetchRow($refund->id->toString());
        self::assertNotFalse($row);
        self::assertSame(RefundStatus::FAILED->value, $row['status']);
        self::assertNotNull($row['failed_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(RefundStatus::INITIATED->value, $otherRow['status']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT payment_id, order_id, amount_in_cents, status, initiated_at, refunded_at, failed_at FROM %s WHERE id = :id', DbalRefundProjector::TABLE),
            ['id' => $id],
        );
    }
}
