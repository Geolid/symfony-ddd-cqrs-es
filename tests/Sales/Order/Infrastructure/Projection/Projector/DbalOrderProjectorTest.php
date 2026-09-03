<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\OrderStatus;
use Sales\Order\Infrastructure\Projection\Projector\DbalOrderProjector;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

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
        $order = OrderBuilder::new()->withCustomerId($customerId)->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame($customerId, $row['customer_id']);
        self::assertSame($order->totalAmountInCents, (int) $row['total_amount_in_cents']);
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
        $other = OrderBuilder::new()->create();
        $this->store($other);
        $order = OrderBuilder::new()->cancelled()->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::CANCELLED->value, $row['status']);
        self::assertNotNull($row['cancelled_at']);
        self::assertNotNull($row['closed_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderStatus::PLACED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnOrderConfirmed(): void
    {
        // Given
        $other = OrderBuilder::new()->create();
        $this->store($other);
        $order = OrderBuilder::new()->confirmed()->create();

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
        $other = OrderBuilder::new()->confirmed()->create();
        $this->store($other);
        $order = OrderBuilder::new()->confirmed()->dispatched()->create();

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
        $other = OrderBuilder::new()->cancelled()->create();
        $this->store($other);
        $order = OrderBuilder::new()->cancelled()->anonymized()->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::CANCELLED->value, $row['status']);
        self::assertNotNull($row['anonymized_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertNull($otherRow['anonymized_at']);
    }

    #[Test]
    public function itProjectsOnOrderDelivered(): void
    {
        // Given
        $other = OrderBuilder::new()->create();
        $this->store($other);
        $order = OrderBuilder::new()->confirmed()->dispatched()->delivered()->create();

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
        $other = OrderBuilder::new()->confirmed()->dispatched()->delivered()->create();
        $this->store($other);
        $order = OrderBuilder::new()->confirmed()->dispatched()->delivered()->completed()->create();

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
        $order = OrderBuilder::new()->confirmed()->dispatched()->delivered();
        $other = $order->create();
        $this->store($other);
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
        $order = OrderBuilder::new()->confirmed()->dispatched()->delivered()->returnRequested();
        $other = $order->create();
        $this->store($other);
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
        $order = OrderBuilder::new()->confirmed()->dispatched()->delivered()->returnRequested();
        $other = $order->create();
        $this->store($other);
        $returnRejectionReason = OrderBuilder::sample('returnRejectionReason');
        $order = $order->returnRejected($returnRejectionReason)->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::RETURN_REJECTED->value, $row['status']);
        self::assertNotNull($row['return_rejected_at']);
        self::assertNotNull($row['closed_at']);
        self::assertSame($returnRejectionReason, $row['return_rejection_reason']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderStatus::RETURN_REQUESTED->value, $otherRow['status']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf(
                'SELECT customer_id, total_amount_in_cents, status, confirmed_at, dispatched_at, delivered_at, completed_at, return_requested_at, returned_at, return_rejected_at, return_rejection_reason, cancelled_at, closed_at, anonymized_at FROM %s WHERE id = :id',
                DbalOrderProjector::TABLE,
            ),
            ['id' => $id],
        );
    }
}
