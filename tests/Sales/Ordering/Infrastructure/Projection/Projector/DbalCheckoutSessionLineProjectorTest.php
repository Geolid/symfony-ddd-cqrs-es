<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Infrastructure\Projection\Projector\DbalCheckoutSessionLineProjector;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{checkout_session_id: string, product_id: string, label: string, unit_price_in_cents: int|string, quantity: int|string}
 */
final class DbalCheckoutSessionLineProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnCheckoutSessionOpenedIntegrationEvent(): void
    {
        // Given
        $otherBuilder = CheckoutSessionBuilder::new();
        $other = $otherBuilder->create();
        $builder = CheckoutSessionBuilder::new();
        $checkoutSession = $builder->create();

        // When
        $this->store($other, $checkoutSession);

        // Then
        $lineId = $builder['lines'][0]->id->toString();
        $row = $this->fetchRow($lineId);
        self::assertNotFalse($row);
        self::assertSame($checkoutSession->id->toString(), $row['checkout_session_id']);
        self::assertSame($builder['lines'][0]->product->id, $row['product_id']);
        self::assertSame($builder['lines'][0]->product->label->value, $row['label']);
        self::assertSame($builder['lines'][0]->product->price->cents, (int) $row['unit_price_in_cents']);
        self::assertSame($builder['lines'][0]->quantity->value, (int) $row['quantity']);

        $otherLineId = $otherBuilder['lines'][0]->id->toString();
        self::assertNotFalse($this->fetchRow($otherLineId));
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $lineId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf(
                'SELECT checkout_session_id, product_id, label, unit_price_in_cents, quantity FROM %s WHERE line_id = :lineId',
                DbalCheckoutSessionLineProjector::TABLE,
            ),
            ['lineId' => $lineId],
        );
    }
}
