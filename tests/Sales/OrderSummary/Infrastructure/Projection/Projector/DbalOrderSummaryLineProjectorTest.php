<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Order\Domain\ValueObject\Product;
use Sales\OrderSummary\Infrastructure\Projection\Projector\DbalOrderSummaryLineProjector;
use Sales\Tests\Order\Support\Builder\OrderBuilder;
use Shared\Domain\ValueObject\Label;
use Shared\Domain\ValueObject\Money;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{position: int, label: string, quantity: int, unit_amount_in_cents: int}
 */
final class DbalOrderSummaryLineProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnOrderPlaced(): void
    {
        // Given
        $order = OrderBuilder::new()->withLines([
            OrderLine::of(Product::of(Uuid::uuid7()->toString(), Label::fromString('Widget'), Money::fromCents(1_000)), 2),
            OrderLine::of(Product::of(Uuid::uuid7()->toString(), Label::fromString('Gadget'), Money::fromCents(3_000)), 1),
        ])->create();

        // When
        $this->store($order);

        // Then
        $rows = $this->fetchRows($order->id->toString());
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
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var list<Row> */
        return $connection->fetchAllAssociative(
            \sprintf(
                'SELECT position, label, quantity, unit_amount_in_cents FROM %s WHERE order_id = :orderId ORDER BY position ASC',
                DbalOrderSummaryLineProjector::TABLE,
            ),
            ['orderId' => $orderId],
        );
    }
}
