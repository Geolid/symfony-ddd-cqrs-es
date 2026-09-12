<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Shopping\Checkout\Infrastructure\Projection\Projector\DbalCheckoutSessionItemProjector;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{label: string, unit_price_in_cents: int, quantity: int}
 */
final class DbalCheckoutSessionItemProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnCheckoutSessionOpened(): void
    {
        // Given
        $other = CheckoutSessionBuilder::new()->create();
        $item = CheckoutSessionBuilder::sample('items')[0];
        $builder = CheckoutSessionBuilder::new()->withItems([$item]);
        $checkoutSession = $builder->create();

        // When
        $this->store($other, $checkoutSession);

        // Then
        $row = $this->fetchRow($checkoutSession->id->toString(), $item->productId);
        self::assertNotFalse($row);
        self::assertSame($item->label->value, $row['label']);
        self::assertSame($item->unitPrice->cents, $row['unit_price_in_cents']);
        self::assertSame($item->quantity->value, $row['quantity']);

        self::assertFalse($this->fetchRow($other->id->toString(), $item->productId));
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $checkoutSessionId, string $productId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT label, unit_price_in_cents, quantity FROM %s WHERE checkout_session_id = :checkoutSessionId AND product_id = :productId', DbalCheckoutSessionItemProjector::TABLE),
            ['checkoutSessionId' => $checkoutSessionId, 'productId' => $productId],
        );
    }
}
