<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Shopping\Checkout\Application\CartStatus;
use Shopping\Checkout\Infrastructure\Projection\Projector\DbalCartProjector;
use Shopping\Tests\Checkout\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{shopper_id: string, status: string}
 */
final class DbalCartProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnCartStarted(): void
    {
        // Given
        $other = CartBuilder::new()->create();
        $builder = CartBuilder::new();
        $cart = $builder->create();

        // When
        $this->store($other, $cart);

        // Then
        $row = $this->fetchRow($cart->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['shopperId'], $row['shopper_id']);
        self::assertSame(CartStatus::ACTIVE->value, $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
    }

    #[Test]
    public function itProjectsOnCartPurchased(): void
    {
        // Given
        $other = CartBuilder::new()->create();
        $cart = CartBuilder::new()->purchased()->create();

        // When
        $this->store($other, $cart);

        // Then
        $row = $this->fetchRow($cart->id->toString());
        self::assertNotFalse($row);
        self::assertSame(CartStatus::PURCHASED->value, $row['status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(CartStatus::ACTIVE->value, $otherRow['status']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT shopper_id, status FROM %s WHERE id = :id', DbalCartProjector::TABLE),
            ['id' => $id],
        );
    }
}
