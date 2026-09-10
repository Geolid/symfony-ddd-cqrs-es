<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\OrderStatus;
use Sales\Ordering\Infrastructure\Projection\Projector\DbalOrderProjector;
use Sales\Tests\Ordering\Support\Builder\OrderBuilder;
use Shared\Application\ErasureStatus;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{shopper_id: string, payment_id: string, total_amount_in_cents: int|string, status: string, confirmed_at: ?string, prepared_at: ?string, dispatched_at: ?string, delivered_at: ?string, cancelled_at: ?string, failed_at: ?string, erasure_status: string}
 */
final class DbalOrderProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnOrderConfirmed(): void
    {
        // Given
        $shopperId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withShopperId($shopperId)->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame($shopperId, $row['shopper_id']);
        self::assertSame($order->paymentId, $row['payment_id']);
        self::assertSame($order->totalAmountInCents, (int) $row['total_amount_in_cents']);
        self::assertSame(OrderStatus::CONFIRMED->value, $row['status']);
        self::assertNotNull($row['confirmed_at']);
        self::assertNull($row['prepared_at']);
        self::assertNull($row['dispatched_at']);
        self::assertNull($row['delivered_at']);
        self::assertNull($row['cancelled_at']);
        self::assertNull($row['failed_at']);
        self::assertSame(ErasureStatus::RETAINED->value, $row['erasure_status']);
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

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderStatus::CONFIRMED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnOrderFailed(): void
    {
        // Given
        $other = OrderBuilder::new()->create();
        $this->store($other);
        $order = OrderBuilder::new()->failed()->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::FAILED->value, $row['status']);
        self::assertNotNull($row['failed_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderStatus::CONFIRMED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnOrderPrepared(): void
    {
        // Given
        $other = OrderBuilder::new()->create();
        $this->store($other);
        $order = OrderBuilder::new()->prepared()->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::PREPARED->value, $row['status']);
        self::assertNotNull($row['prepared_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderStatus::CONFIRMED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnOrderDispatched(): void
    {
        // Given
        $other = OrderBuilder::new()->prepared()->create();
        $this->store($other);
        $order = OrderBuilder::new()->prepared()->dispatched()->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::DISPATCHED->value, $row['status']);
        self::assertNotNull($row['dispatched_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderStatus::PREPARED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnOrderDelivered(): void
    {
        // Given
        $other = OrderBuilder::new()->prepared()->dispatched()->create();
        $this->store($other);
        $order = OrderBuilder::new()->prepared()->dispatched()->delivered()->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::DELIVERED->value, $row['status']);
        self::assertNotNull($row['delivered_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderStatus::DISPATCHED->value, $otherRow['status']);
        self::assertNull($otherRow['delivered_at']);
    }

    #[Test]
    public function itProjectsOnOrderErasureApproved(): void
    {
        // Given
        $other = OrderBuilder::new()->create();
        $this->store($other);
        $order = OrderBuilder::new()->erasureApproved()->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ErasureStatus::APPROVED->value, $row['erasure_status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ErasureStatus::RETAINED->value, $otherRow['erasure_status']);
    }

    #[Test]
    public function itProjectsOnOrderErased(): void
    {
        // Given
        $other = OrderBuilder::new()->prepared()->dispatched()->delivered()->create();
        $this->store($other);
        $order = OrderBuilder::new()->prepared()->dispatched()->delivered()->erasureApproved()->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ErasureStatus::ERASED->value, $row['erasure_status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ErasureStatus::RETAINED->value, $otherRow['erasure_status']);
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
                'SELECT shopper_id, payment_id, total_amount_in_cents, status, confirmed_at, prepared_at, dispatched_at, delivered_at, cancelled_at, failed_at, erasure_status FROM %s WHERE id = :id',
                DbalOrderProjector::TABLE,
            ),
            ['id' => $id],
        );
    }
}
