<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\OrderSummary\Infrastructure\Persistence\Projection\Projector\DbalOrderSummaryLineProjector;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Shared\Domain\ValueObject\Money;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{position: int, label: string, quantity: int, unit_amount_in_cents: int}
 */
final class DbalOrderSummaryLineProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsEachLineOnOrderPlaced(): void
    {
        // Given
        $order = OrderTestFactory::new()->withLines([
            OrderLine::of('Widget', 2, Money::fromCents(1_000)),
            OrderLine::of('Gadget', 1, Money::fromCents(3_000)),
        ])->create();

        // When
        $this->store($order);

        // Then
        $rows = $this->fetchRows($order->id()->toString());
        self::assertCount(2, $rows);
        self::assertSame(0, $rows[0]['position']);
        self::assertSame('Widget', $rows[0]['label']);
        self::assertSame(2, $rows[0]['quantity']);
        self::assertSame(1_000, $rows[0]['unit_amount_in_cents']);
        self::assertSame(1, $rows[1]['position']);
        self::assertSame('Gadget', $rows[1]['label']);
    }

    /**
     * @return list<Row>
     */
    private function fetchRows(string $orderId): array
    {
        /** @var list<Row> */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAllAssociative(
            \sprintf(
                'SELECT position, label, quantity, unit_amount_in_cents FROM %s WHERE order_id = :orderId ORDER BY position ASC',
                DbalOrderSummaryLineProjector::TABLE,
            ),
            ['orderId' => $orderId],
        );
    }
}
