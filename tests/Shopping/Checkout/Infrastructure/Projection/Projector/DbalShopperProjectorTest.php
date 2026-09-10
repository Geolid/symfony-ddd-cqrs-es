<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\ErasureStatus;
use Shopping\Checkout\Infrastructure\Projection\Projector\DbalShopperProjector;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{email: string, registered_at: string, erasure_status: string}
 */
final class DbalShopperProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnShopperRegistered(): void
    {
        // Given
        $builder = ShopperBuilder::new();
        $shopper = $builder->create();

        // When
        $this->store($shopper);

        // Then
        $row = $this->fetchRow($shopper->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['email']->value, $row['email']);
        self::assertSame($builder['registeredAt']->format('Y-m-d H:i:s'), $row['registered_at']);
        self::assertSame(ErasureStatus::RETAINED->value, $row['erasure_status']);
    }

    #[Test]
    public function itProjectsOnShopperErasureRequested(): void
    {
        // Given
        $other = ShopperBuilder::new()->create();
        $this->store($other);
        $shopper = ShopperBuilder::new()->erasureRequested()->create();

        // When
        $this->store($shopper);

        // Then
        $row = $this->fetchRow($shopper->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ErasureStatus::REQUESTED->value, $row['erasure_status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ErasureStatus::RETAINED->value, $otherRow['erasure_status']);
    }

    #[Test]
    public function itProjectsOnShopperErasureCancelled(): void
    {
        // Given
        $other = ShopperBuilder::new()->erasureRequested()->create();
        $this->store($other);
        $shopper = ShopperBuilder::new()->erasureRequested()->erasureCancelled()->create();

        // When
        $this->store($shopper);

        // Then
        $row = $this->fetchRow($shopper->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ErasureStatus::RETAINED->value, $row['erasure_status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ErasureStatus::REQUESTED->value, $otherRow['erasure_status']);
    }

    #[Test]
    public function itRemovesOnShopperErased(): void
    {
        // Given
        $otherBuilder = ShopperBuilder::new();
        $other = $otherBuilder->create();
        $this->store($other);
        $shopper = ShopperBuilder::new()->erasureRequested()->erased()->create();

        // When
        $this->store($shopper);

        // Then
        self::assertFalse($this->fetchRow($shopper->id->toString()));

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($otherBuilder['email']->value, $otherRow['email']);
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT email, registered_at, erasure_status FROM %s WHERE id = :id', DbalShopperProjector::TABLE),
            ['id' => $id],
        );
    }
}
