<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Enum\OrderStatus;
use Sales\Order\Infrastructure\Persistence\Projection\Projector\DbalOrderProjector;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{customer_id: string, total_amount_in_cents: int|string, status: string, cancelled_at: ?string}
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
        self::assertNull($row['cancelled_at']);
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
    public function itProjectsTheErasureOnOrderBillingAddressErased(): void
    {
        // Given
        $other = OrderTestFactory::new()->store();

        // When
        $order = OrderTestFactory::new()->billingAddressErased()->store();

        // Then
        self::assertFalse($this->fetchRow($order->id()->toString()));

        $otherRow = $this->fetchRow($other->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($other->customerId(), $otherRow['customer_id']);
        self::assertSame(OrderStatus::PLACED->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsTheCompletionOnOrderCompleted(): void
    {
        // Given
        $other = OrderTestFactory::new()->store();

        // When
        $order = OrderTestFactory::new()->confirmed()->dispatched()->completed()->store();

        // Then
        $row = $this->fetchRow($order->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(OrderStatus::COMPLETED->value, $row['status']);

        $otherRow = $this->fetchRow($other->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(OrderStatus::PLACED->value, $otherRow['status']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf(
                'SELECT customer_id, total_amount_in_cents, status, cancelled_at FROM %s WHERE id = :id',
                DbalOrderProjector::TABLE,
            ),
            ['id' => $id],
        );
    }
}
