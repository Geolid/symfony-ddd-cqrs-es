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
 * @phpstan-type Row array{buyer_id: string, total_amount_in_cents: int|string, status: string, confirmed_at: ?string, prepared_at: ?string, dispatched_at: ?string, delivered_at: ?string, return_requested_at: ?string, returned_at: ?string, disputed_at: ?string, cancelled_at: ?string}
 */
final class DbalOrderProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnOrderPlaced(): void
    {
        // Given
        $buyerId = Uuid::uuid7()->toString();
        $order = OrderBuilder::new()->withBuyerId($buyerId)->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame($buyerId, $row['buyer_id']);
        self::assertSame($order->totalAmountInCents, (int) $row['total_amount_in_cents']);
        self::assertSame(OrderStatus::PLACED->value, $row['status']);
        self::assertNull($row['confirmed_at']);
        self::assertNull($row['prepared_at']);
        self::assertNull($row['dispatched_at']);
        self::assertNull($row['delivered_at']);
        self::assertNull($row['return_requested_at']);
        self::assertNull($row['returned_at']);
        self::assertNull($row['disputed_at']);
        self::assertNull($row['cancelled_at']);
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
        self::assertSame(OrderStatus::PLACED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnOrderAborted(): void
    {
        // Given
        $other = OrderBuilder::new()->create();
        $this->store($other);
        $order = OrderBuilder::new()->aborted()->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::CANCELLED->value, $row['status']);
        self::assertNotNull($row['cancelled_at']);

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
    public function itProjectsOnOrderPrepared(): void
    {
        // Given
        $other = OrderBuilder::new()->confirmed()->create();
        $this->store($other);
        $order = OrderBuilder::new()->confirmed()->prepared()->create();

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
        $other = OrderBuilder::new()->confirmed()->prepared()->create();
        $this->store($other);
        $order = OrderBuilder::new()->confirmed()->prepared()->dispatched()->create();

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
        $other = OrderBuilder::new()->confirmed()->prepared()->dispatched()->create();
        $this->store($other);
        $order = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->create();

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
    public function itProjectsOnOrderReturnRequested(): void
    {
        // Given
        $other = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->create();
        $this->store($other);
        $order = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->returnRequested()->create();

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
        $other = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->returnRequested()->create();
        $this->store($other);
        $order = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->returnRequested()->returned()->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::RETURNED->value, $row['status']);
        self::assertNotNull($row['returned_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderStatus::RETURN_REQUESTED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnOrderDisputed(): void
    {
        // Given
        $other = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->returnRequested()->create();
        $this->store($other);
        $order = OrderBuilder::new()->confirmed()->prepared()->dispatched()->delivered()->returnRequested()->disputed()->create();

        // When
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::DISPUTED->value, $row['status']);
        self::assertNotNull($row['disputed_at']);

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
                'SELECT buyer_id, total_amount_in_cents, status, confirmed_at, prepared_at, dispatched_at, delivered_at, return_requested_at, returned_at, disputed_at, cancelled_at FROM %s WHERE id = :id',
                DbalOrderProjector::TABLE,
            ),
            ['id' => $id],
        );
    }
}
