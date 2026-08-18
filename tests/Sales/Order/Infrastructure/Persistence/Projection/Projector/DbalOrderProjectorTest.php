<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Order\Infrastructure\Persistence\Projection\Projector\DbalOrderProjector;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{customer_id: string, total_amount_in_cents: int|string, status: string, confirmed_at: ?string, dispatched_at: ?string, delivered_at: ?string, completed_at: ?string, cancelled_at: ?string, anonymized_at: ?string}
 */
final class DbalOrderProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsTheOrderOnOrderPlaced(): void
    {
        // When
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->withTotalAmountInCents(2_500)->store();

        // Then
        $row = $this->fetchRow($order->id()->toString());
        self::assertNotFalse($row);
        self::assertSame($customerId, $row['customer_id']);
        self::assertSame(2_500, (int) $row['total_amount_in_cents']);
        self::assertSame(OrderStatus::PLACED->value, $row['status']);
        self::assertNull($row['confirmed_at']);
        self::assertNull($row['dispatched_at']);
        self::assertNull($row['delivered_at']);
        self::assertNull($row['completed_at']);
        self::assertNull($row['cancelled_at']);
        self::assertNull($row['anonymized_at']);
    }

    #[Test]
    public function itProjectsTheCancellationOnOrderCancelled(): void
    {
        // When
        $order = OrderTestFactory::new()->cancelled()->store();

        // Then
        $row = $this->fetchRow($order->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::CANCELLED->value, $row['status']);
        self::assertNotNull($row['cancelled_at']);
    }

    #[Test]
    public function itProjectsTheConfirmationOnOrderConfirmed(): void
    {
        // Given
        $other = OrderTestFactory::new()->store();

        // When
        $order = OrderTestFactory::new()->confirmed()->store();

        // Then
        $row = $this->fetchRow($order->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::CONFIRMED->value, $row['status']);
        self::assertNotNull($row['confirmed_at']);

        $otherRow = $this->fetchRow($other->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderStatus::PLACED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsTheDispatchOnOrderDispatched(): void
    {
        // Given
        $other = OrderTestFactory::new()->confirmed()->store();

        // When
        $order = OrderTestFactory::new()->confirmed()->dispatched()->store();

        // Then
        $row = $this->fetchRow($order->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::DISPATCHED->value, $row['status']);
        self::assertNotNull($row['dispatched_at']);

        $otherRow = $this->fetchRow($other->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderStatus::CONFIRMED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsTheAnonymizationOnOrderAnonymized(): void
    {
        // When
        $order = OrderTestFactory::new()->anonymized()->store();

        // Then
        $row = $this->fetchRow($order->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::PLACED->value, $row['status']);
        self::assertNotNull($row['anonymized_at']);
    }

    #[Test]
    public function itProjectsTheDeliveryOnOrderDelivered(): void
    {
        // Given
        $other = OrderTestFactory::new()->store();

        // When
        $order = OrderTestFactory::new()->confirmed()->dispatched()->delivered()->store();

        // Then
        $row = $this->fetchRow($order->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::DELIVERED->value, $row['status']);
        self::assertNotNull($row['delivered_at']);

        $otherRow = $this->fetchRow($other->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderStatus::PLACED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsTheCompletionOnOrderCompleted(): void
    {
        // Given
        $other = OrderTestFactory::new()->confirmed()->dispatched()->delivered()->store();

        // When
        $order = OrderTestFactory::new()->confirmed()->dispatched()
            ->delivered(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))
            ->completed(new \DateTimeImmutable('2026-02-01T00:00:00+00:00'))
            ->store();

        // Then
        $row = $this->fetchRow($order->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::COMPLETED->value, $row['status']);
        self::assertNotNull($row['completed_at']);

        $otherRow = $this->fetchRow($other->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderStatus::DELIVERED->value, $otherRow['status']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf(
                'SELECT customer_id, total_amount_in_cents, status, confirmed_at, dispatched_at, delivered_at, completed_at, cancelled_at, anonymized_at FROM %s WHERE id = :id',
                DbalOrderProjector::TABLE,
            ),
            ['id' => $id],
        );
    }
}
