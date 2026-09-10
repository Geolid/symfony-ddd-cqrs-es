<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Domain\Shared\ValueObject\Quantity;
use Sales\Ordering\Infrastructure\Projection\Projector\DbalCartLineProjector;
use Sales\Tests\Ordering\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{line_id: string, cart_id: string, product_id: string, label: string, unit_price_in_cents: int|string, quantity: int|string}
 */
final class DbalCartLineProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnCartLineAdded(): void
    {
        // Given
        $builder = CartBuilder::new()->lineAdded();
        $cart = $builder->create();

        // When
        $this->store($cart);

        // Then
        $row = $this->fetchRow($builder->lineId()->toString());
        self::assertNotFalse($row);
        self::assertSame($cart->id->toString(), $row['cart_id']);
        self::assertSame($builder['product']->id, $row['product_id']);
        self::assertSame($builder['product']->label->value, $row['label']);
        self::assertSame($builder['product']->price->cents, (int) $row['unit_price_in_cents']);
        self::assertSame($builder['quantity']->value, (int) $row['quantity']);
    }

    #[Test]
    public function itAccumulatesQuantityWhenCartLineAddedForAnAlreadyExistingLine(): void
    {
        // Given
        $otherBuilder = CartBuilder::new()->lineAdded();
        $other = $otherBuilder->create();
        $this->store($other);
        $builder = CartBuilder::new()->lineAdded(quantity: Quantity::of(2));
        $cart = $builder->create();
        $this->store($cart);

        // When
        $cart->addLine($builder['product'], Quantity::of(3), $builder['addedAt']);
        $this->store($cart);

        // Then
        $row = $this->fetchRow($builder->lineId()->toString());
        self::assertNotFalse($row);
        self::assertSame(5, (int) $row['quantity']);

        $otherRow = $this->fetchRow($otherBuilder->lineId()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($otherBuilder['quantity']->value, (int) $otherRow['quantity']);
    }

    #[Test]
    public function itRemovesOnCartLineRemoved(): void
    {
        // Given
        $otherBuilder = CartBuilder::new()->lineAdded();
        $other = $otherBuilder->create();
        $this->store($other);
        $builder = CartBuilder::new()->lineAdded();
        $cart = $builder->create();
        $this->store($cart);
        $lineId = $builder->lineId();

        // When
        $cart->removeLine($lineId, $builder['removedAt']);
        $this->store($cart);

        // Then
        self::assertFalse($this->fetchRow($lineId->toString()));

        $otherRow = $this->fetchRow($otherBuilder->lineId()->toString());
        self::assertNotFalse($otherRow);
    }

    #[Test]
    public function itProjectsOnCartLineQuantityChanged(): void
    {
        // Given
        $otherBuilder = CartBuilder::new()->lineAdded();
        $other = $otherBuilder->create();
        $this->store($other);
        $builder = CartBuilder::new()->lineAdded();
        $cart = $builder->create();
        $this->store($cart);
        $lineId = $builder->lineId();

        // When
        $cart->changeQuantity($lineId, Quantity::of(9), $builder['changedAt']);
        $this->store($cart);

        // Then
        $row = $this->fetchRow($lineId->toString());
        self::assertNotFalse($row);
        self::assertSame(9, (int) $row['quantity']);

        $otherRow = $this->fetchRow($otherBuilder->lineId()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($otherBuilder['quantity']->value, (int) $otherRow['quantity']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $lineId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT line_id, cart_id, product_id, label, unit_price_in_cents, quantity FROM %s WHERE line_id = :lineId', DbalCartLineProjector::TABLE),
            ['lineId' => $lineId],
        );
    }
}
