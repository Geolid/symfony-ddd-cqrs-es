<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Infrastructure\Projection\Projector;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Buyer\Infrastructure\Projection\Projector\DbalBuyerProjector;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Shared\Application\ErasureStatus;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{email: string, registered_at: string, erasure_status: string}
 */
final class DbalBuyerProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnBuyerRegistered(): void
    {
        // Given
        $builder = BuyerBuilder::new();
        $buyer = $builder->create();

        // When
        $this->store($buyer);

        // Then
        $row = $this->fetchRow($buyer->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['email']->value, $row['email']);
        self::assertSame($builder['registeredAt']->format('Y-m-d H:i:s'), $row['registered_at']);
        self::assertSame(ErasureStatus::RETAINED->value, $row['erasure_status']);
    }

    #[Test]
    public function itProjectsOnBuyerErasureRequested(): void
    {
        // Given
        $other = BuyerBuilder::new()->create();
        $this->store($other);
        $buyer = BuyerBuilder::new()->erasureRequested()->create();

        // When
        $this->store($buyer);

        // Then
        $row = $this->fetchRow($buyer->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ErasureStatus::REQUESTED->value, $row['erasure_status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ErasureStatus::RETAINED->value, $otherRow['erasure_status']);
    }

    #[Test]
    public function itProjectsOnBuyerErasureCancelled(): void
    {
        // Given
        $other = BuyerBuilder::new()->erasureRequested()->create();
        $this->store($other);
        $buyer = BuyerBuilder::new()->erasureRequested()->erasureCancelled()->create();

        // When
        $this->store($buyer);

        // Then
        $row = $this->fetchRow($buyer->id->toString());
        self::assertNotFalse($row);
        self::assertSame(ErasureStatus::RETAINED->value, $row['erasure_status']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(ErasureStatus::REQUESTED->value, $otherRow['erasure_status']);
    }

    #[Test]
    public function itRemovesOnBuyerErased(): void
    {
        // Given
        $otherBuilder = BuyerBuilder::new();
        $other = $otherBuilder->create();
        $this->store($other);
        $buyer = BuyerBuilder::new()->erasureRequested()->erased()->create();

        // When
        $this->store($buyer);

        // Then
        self::assertFalse($this->fetchRow($buyer->id->toString()));

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
            \sprintf('SELECT email, registered_at, erasure_status FROM %s WHERE id = :id', DbalBuyerProjector::TABLE),
            ['id' => $id],
        );
    }
}
