<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shopping\Cart\Domain\ValueObject\Quantity;
use Shopping\Checkout\Infrastructure\Projection\Projector\DbalCartItemProjector;
use Shopping\Tests\Cart\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{quantity: int}
 */
final class DbalCartItemProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnCartProductAdded(): void
    {
        // Given
        $otherProductId = Uuid::uuid7()->toString();
        $other = CartBuilder::new()->productAdded($otherProductId)->create();
        $this->store($other);

        $productId = Uuid::uuid7()->toString();
        $builder = CartBuilder::new()->productAdded($productId);
        $cart = $builder->create();

        // When
        $this->store($cart);

        // Then
        $row = $this->fetchRow($cart->id->toString(), $productId);
        self::assertNotFalse($row);
        self::assertSame($builder['productAdditions'][0]['quantity']->value, $row['quantity']);

        $otherRow = $this->fetchRow($other->id->toString(), $otherProductId);
        self::assertNotFalse($otherRow);
    }

    #[Test]
    public function itProjectsOnCartProductAddedWhenAlreadyPresent(): void
    {
        // Given
        $productId = Uuid::uuid7()->toString();
        $cart = CartBuilder::new()
            ->productAdded($productId, Quantity::of(2))
            ->productAdded($productId, Quantity::of(3))
            ->create();

        // When
        $this->store($cart);

        // Then
        $row = $this->fetchRow($cart->id->toString(), $productId);
        self::assertNotFalse($row);
        self::assertSame(5, $row['quantity']);
    }

    #[Test]
    public function itRemovesOnCartProductRemoved(): void
    {
        // Given
        $productId = Uuid::uuid7()->toString();
        $other = CartBuilder::new()->productAdded($productId)->create();
        $this->store($other);

        $cart = CartBuilder::new()->productAdded($productId)->productRemoved()->create();

        // When
        $this->store($cart);

        // Then
        self::assertFalse($this->fetchRow($cart->id->toString(), $productId));

        $otherRow = $this->fetchRow($other->id->toString(), $productId);
        self::assertNotFalse($otherRow);
    }

    #[Test]
    public function itProjectsOnCartProductQuantityChanged(): void
    {
        // Given
        $productId = Uuid::uuid7()->toString();
        $other = CartBuilder::new()->productAdded($productId, $otherQuantity = Quantity::of(4))->create();
        $this->store($other);

        $cart = CartBuilder::new()
            ->productAdded($productId, Quantity::of(2))
            ->productQuantityChanged(quantity: $newQuantity = Quantity::of(9))
            ->create();

        // When
        $this->store($cart);

        // Then
        $row = $this->fetchRow($cart->id->toString(), $productId);
        self::assertNotFalse($row);
        self::assertSame($newQuantity->value, $row['quantity']);

        $otherRow = $this->fetchRow($other->id->toString(), $productId);
        self::assertNotFalse($otherRow);
        self::assertSame($otherQuantity->value, $otherRow['quantity']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $cartId, string $productId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT quantity FROM %s WHERE cart_id = :cartId AND product_id = :productId', DbalCartItemProjector::TABLE),
            ['cartId' => $cartId, 'productId' => $productId],
        );
    }
}
