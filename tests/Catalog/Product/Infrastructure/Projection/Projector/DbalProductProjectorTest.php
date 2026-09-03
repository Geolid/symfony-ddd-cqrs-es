<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Infrastructure\Projection\Projector;

use Catalog\Product\Infrastructure\Projection\Projector\DbalProductProjector;
use Catalog\Tests\Product\Support\Builder\ProductBuilder;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{label: string, unit_amount_in_cents: int, listed_at: string, repriced_at: string|null}
 */
final class DbalProductProjectorTest extends AbstractIntegrationTestCase
{
    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    #[Test]
    public function itProjectsOnProductListed(): void
    {
        // Given
        $builder = ProductBuilder::new();
        $product = $builder->create();

        // When
        $this->store($product);

        // Then
        $row = $this->fetchRow($product->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['label']->value, $row['label']);
        self::assertSame($builder['unitAmount']->cents, (int) $row['unit_amount_in_cents']);
        self::assertSame($builder['listedAt']->format(self::DATE_FORMAT), $row['listed_at']);
        self::assertNull($row['repriced_at']);
    }

    #[Test]
    public function itProjectsOnProductRepriced(): void
    {
        // Given
        $otherBuilder = ProductBuilder::new();
        $other = $otherBuilder->create();
        $this->store($other);

        $builder = ProductBuilder::new()->repriced();
        $product = $builder->create();

        // When
        $this->store($product);

        // Then
        $row = $this->fetchRow($product->id->toString());
        self::assertNotFalse($row);
        self::assertSame($builder['unitAmount']->cents, (int) $row['unit_amount_in_cents']);
        self::assertSame($builder['repricedAt']->format(self::DATE_FORMAT), $row['repriced_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($otherBuilder['unitAmount']->cents, (int) $otherRow['unit_amount_in_cents']);
        self::assertNull($otherRow['repriced_at']);
    }

    #[Test]
    public function itRemovesOnProductDelisted(): void
    {
        // Given
        $other = ProductBuilder::new()->create();
        $this->store($other);

        $product = ProductBuilder::new()->delisted()->create();

        // When
        $this->store($product);

        // Then
        self::assertFalse($this->fetchRow($product->id->toString()));
        self::assertNotFalse($this->fetchRow($other->id->toString()));
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf('SELECT label, unit_amount_in_cents, listed_at, repriced_at FROM %s WHERE id = :id', DbalProductProjector::TABLE),
            ['id' => $id],
        );
    }
}
