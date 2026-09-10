<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Infrastructure\Projection\Projector\DbalCartLineProjector;
use Shopping\Tests\Checkout\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{cart_id: string, product_id: string, label: string, unit_price_in_cents: int|string, quantity: int|string}
 */
final class DbalCartLineProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnCartCheckedOutIntegrationEvent(): void
    {
        // Given
        $other = CartBuilder::new()->lineAdded()->checkedOut()->create();
        $builder = CartBuilder::new()->lineAdded()->checkedOut();
        $cart = $builder->create();

        // When
        $this->store($other, $cart);

        // Then
        $lineId = $cart->lines()[0]->id->toString();
        $row = $this->fetchRow($lineId);
        self::assertNotFalse($row);
        self::assertSame($cart->id->toString(), $row['cart_id']);
        self::assertSame($builder['product']->id, $row['product_id']);
        self::assertSame($builder['product']->label->value, $row['label']);
        self::assertSame($builder['product']->price->cents, (int) $row['unit_price_in_cents']);
        self::assertSame($builder['quantity']->value, (int) $row['quantity']);

        $otherLineId = $other->lines()[0]->id->toString();
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
                'SELECT cart_id, product_id, label, unit_price_in_cents, quantity FROM %s WHERE line_id = :lineId',
                DbalCartLineProjector::TABLE,
            ),
            ['lineId' => $lineId],
        );
    }
}
