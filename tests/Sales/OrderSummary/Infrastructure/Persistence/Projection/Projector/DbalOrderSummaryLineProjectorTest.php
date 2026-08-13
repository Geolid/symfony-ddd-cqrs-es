<?php

declare(strict_types=1);

namespace Sales\Tests\OrderSummary\Infrastructure\Persistence\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Domain\ValueObject\OrderLine;
use Sales\Order\Domain\ValueObject\Product;
use Sales\OrderSummary\Infrastructure\Persistence\Projection\Projector\DbalOrderSummaryLineProjector;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Shared\Domain\ValueObject\Label;
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
        // When
        $order = OrderTestFactory::new()->withLines([
            OrderLine::of(Product::of(Uuid::uuid7()->toString(), Label::fromString('Widget'), Money::fromCents(1_000)), 2),
            OrderLine::of(Product::of(Uuid::uuid7()->toString(), Label::fromString('Gadget'), Money::fromCents(3_000)), 1),
        ])->store();

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
