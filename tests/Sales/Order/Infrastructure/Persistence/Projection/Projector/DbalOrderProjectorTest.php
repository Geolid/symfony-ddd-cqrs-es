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
 * @phpstan-type Row array{customer_id: string, total_amount_in_cents: int|string, status: string, confirmed_at: ?string, dispatched_at: ?string, delivered_at: ?string, completed_at: ?string, return_requested_at: ?string, returned_at: ?string, return_rejected_at: ?string, return_rejection_reason: ?string, cancelled_at: ?string, closed_at: ?string, anonymized_at: ?string}
 */
final class DbalOrderProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnOrderPlaced(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->withTotalAmountInCents(2_500)->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame($customerId, $row['customer_id']);
        self::assertSame(2_500, (int) $row['total_amount_in_cents']);
        self::assertSame(OrderStatus::PLACED->value, $row['status']);
        self::assertNull($row['confirmed_at']);
        self::assertNull($row['dispatched_at']);
        self::assertNull($row['delivered_at']);
        self::assertNull($row['completed_at']);
        self::assertNull($row['cancelled_at']);
        self::assertNull($row['closed_at']);
        self::assertNull($row['anonymized_at']);
    }

    #[Test]
    public function itProjectsOnOrderCancelled(): void
    {
        // Given
        $order = OrderTestFactory::new()->cancelled()->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::CANCELLED->value, $row['status']);
        self::assertNotNull($row['cancelled_at']);
        self::assertNotNull($row['closed_at']);
    }

    #[Test]
    public function itProjectsOnOrderConfirmed(): void
    {
        // Given
        $other = OrderTestFactory::new()->store();
        $order = OrderTestFactory::new()->confirmed()->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::CONFIRMED->value, $row['status']);
        self::assertNotNull($row['confirmed_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderStatus::PLACED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnOrderDispatched(): void
    {
        // Given
        $other = OrderTestFactory::new()->confirmed()->store();
        $order = OrderTestFactory::new()->confirmed()->dispatched()->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::DISPATCHED->value, $row['status']);
        self::assertNotNull($row['dispatched_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderStatus::CONFIRMED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnOrderAnonymized(): void
    {
        // Given
        $order = OrderTestFactory::new()
            ->cancelled(new \DateTimeImmutable('2016-01-01T00:00:00+00:00'))
            ->anonymized(new \DateTimeImmutable('2026-02-01T00:00:00+00:00'))
            ->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::CANCELLED->value, $row['status']);
        self::assertNotNull($row['anonymized_at']);
    }

    #[Test]
    public function itProjectsOnOrderDelivered(): void
    {
        // Given
        $other = OrderTestFactory::new()->store();
        $order = OrderTestFactory::new()->confirmed()->dispatched()->delivered()->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::DELIVERED->value, $row['status']);
        self::assertNotNull($row['delivered_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderStatus::PLACED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnOrderCompleted(): void
    {
        // Given
        $other = OrderTestFactory::new()->confirmed()->dispatched()->delivered()->store();
        $order = OrderTestFactory::new()->confirmed()->dispatched()
            ->delivered(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))
            ->completed(new \DateTimeImmutable('2026-02-01T00:00:00+00:00'))
            ->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::COMPLETED->value, $row['status']);
        self::assertNotNull($row['completed_at']);
        self::assertNotNull($row['closed_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderStatus::DELIVERED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnOrderReturnRequested(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->dispatched()->delivered();
        $other = $order->store();
        $order = $order->returnRequested()->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::RETURN_REQUESTED->value, $row['status']);
        self::assertNotNull($row['return_requested_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderStatus::DELIVERED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnOrderReturned(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->dispatched()->delivered()->returnRequested();
        $other = $order->store();
        $order = $order->returned()->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::RETURNED->value, $row['status']);
        self::assertNotNull($row['returned_at']);
        self::assertNotNull($row['closed_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderStatus::RETURN_REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnOrderReturnRejected(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->dispatched()->delivered()->returnRequested();
        $other = $order->store();
        $order = $order->returnRejected('item damaged beyond resale')->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::RETURN_REJECTED->value, $row['status']);
        self::assertNotNull($row['return_rejected_at']);
        self::assertNotNull($row['closed_at']);
        self::assertSame('item damaged beyond resale', $row['return_rejection_reason']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderStatus::RETURN_REQUESTED->value, $otherRow['status']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf(
                'SELECT customer_id, total_amount_in_cents, status, confirmed_at, dispatched_at, delivered_at, completed_at, return_requested_at, returned_at, return_rejected_at, return_rejection_reason, cancelled_at, closed_at, anonymized_at FROM %s WHERE id = :id',
                DbalOrderProjector::TABLE,
            ),
            ['id' => $id],
        );
    }
}
