<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Projection\Projector;

use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Infrastructure\Projection\Projector\DbalListedProductProjector;
use Support\TestCase\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{label: string, unit_price_in_cents: int|string}
 */
final class DbalListedProductProjectorTest extends AbstractIntegrationTestCase
{
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
        self::assertSame($builder['unitPrice']->cents, (int) $row['unit_price_in_cents']);
    }

    #[Test]
    public function itProjectsOnProductRepriced(): void
    {
        // Given
        $otherBuilder = ProductBuilder::new();
        $other = $otherBuilder->create();
        $this->store($other);
        $product = ProductBuilder::new()->repriced(2_000)->create();

        // When
        $this->store($product);

        // Then
        $row = $this->fetchRow($product->id->toString());
        self::assertNotFalse($row);
        self::assertSame(2_000, (int) $row['unit_price_in_cents']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($otherBuilder['unitPrice']->cents, (int) $otherRow['unit_price_in_cents']);
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
    private function fetchRow(string $productId): array|false
    {
        $connection = $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class);

        /** @var Row|false */
        return $connection->fetchAssociative(
            \sprintf(
                'SELECT label, unit_price_in_cents FROM %s WHERE product_id = :productId',
                DbalListedProductProjector::TABLE,
            ),
            ['productId' => $productId],
        );
    }
}
