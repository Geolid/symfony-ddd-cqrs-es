<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
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
        $order = OrderTestFactory::new()->withCustomerId('customer-1')->withTotalAmountInCents(2_500)->create();
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('customer-1', $row['customer_id']);
        self::assertSame(2_500, (int) $row['total_amount_in_cents']);
        self::assertSame('placed', $row['status']);
        self::assertNull($row['cancelled_at']);
    }

    #[Test]
    public function itProjectsTheCancellationOnOrderCancelled(): void
    {
        // When
        $order = OrderTestFactory::new()->cancelled()->create();
        $this->store($order);

        // Then
        $row = $this->fetchRow($order->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('cancelled', $row['status']);
        self::assertNotNull($row['cancelled_at']);
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
