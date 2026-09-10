<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Ordering\Application\CartStatus;
use Sales\Ordering\Infrastructure\Projection\Projector\DbalCartProjector;
use Sales\Tests\Ordering\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{id: string, buyer_id: string, status: string}
 */
final class DbalCartProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnCartStarted(): void
    {
        // Given
        $builder = CartBuilder::new();
        $cart = $builder->create();

        // When
        $this->store($cart);

        // Then
        $row = $this->fetchRow($cart->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['buyerId'], $row['buyer_id']);
        self::assertSame(CartStatus::ACTIVE->value, $row['status']);
    }

    #[Test]
    public function itProjectsOnCartCheckedOut(): void
    {
        // Given
        $other = CartBuilder::new()->create();
        $this->store($other);
        $cart = CartBuilder::new()->lineAdded()->checkedOut()->create();

        // When
        $this->store($cart);

        // Then
        $row = $this->fetchRow($cart->id->toString());
        self::assertNotFalse($row);
        self::assertSame(CartStatus::CHECKOUT->value, $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(CartStatus::ACTIVE->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnCartCheckoutAbandoned(): void
    {
        // Given
        $other = CartBuilder::new()->lineAdded()->checkedOut()->create();
        $this->store($other);
        $cart = CartBuilder::new()->lineAdded()->checkedOut()->checkoutAbandoned()->create();

        // When
        $this->store($cart);

        // Then
        $row = $this->fetchRow($cart->id->toString());
        self::assertNotFalse($row);
        self::assertSame(CartStatus::ACTIVE->value, $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(CartStatus::CHECKOUT->value, $otherRow['status']);
    }

    #[Test]
    public function itProjectsOnCartConverted(): void
    {
        // Given
        $other = CartBuilder::new()->lineAdded()->checkedOut()->create();
        $this->store($other);
        $cart = CartBuilder::new()->lineAdded()->checkedOut()->converted()->create();

        // When
        $this->store($cart);

        // Then
        $row = $this->fetchRow($cart->id->toString());
        self::assertNotFalse($row);
        self::assertSame(CartStatus::CONVERTED->value, $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(CartStatus::CHECKOUT->value, $otherRow['status']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT id, buyer_id, status FROM %s WHERE id = :id', DbalCartProjector::TABLE),
            ['id' => $id],
        );
    }
}
