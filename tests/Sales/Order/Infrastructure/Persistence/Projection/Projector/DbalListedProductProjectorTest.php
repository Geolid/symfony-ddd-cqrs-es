<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\Projection\Projector;

use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Infrastructure\Persistence\Projection\Projector\DbalListedProductProjector;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{label: string, unit_amount_in_cents: int|string}
 */
final class DbalListedProductProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnProductListed(): void
    {
        // When
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->store();

        // Then
        $row = $this->fetchRow($product->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('Espresso cups, set of 6', $row['label']);
        self::assertSame(1_750, (int) $row['unit_amount_in_cents']);
    }

    #[Test]
    public function itProjectsOnProductRepriced(): void
    {
        // Given
        $other = ProductTestFactory::new()->withUnitAmountInCents(83)->store();
        $product = ProductTestFactory::new()->withUnitAmountInCents(1_750)->repriced(2_000)->create();

        // When
        $this->store($product);

        // Then
        $row = $this->fetchRow($product->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(2_000, (int) $row['unit_amount_in_cents']);

        $otherRow = $this->fetchRow($other->id()->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(83, (int) $otherRow['unit_amount_in_cents']);
    }

    #[Test]
    public function itRemovesOnProductDelisted(): void
    {
        // Given
        $other = ProductTestFactory::new()->store();
        $product = ProductTestFactory::new()->delisted()->create();

        // When
        $this->store($product);

        // Then
        self::assertFalse($this->fetchRow($product->id()->toString()));
        self::assertNotFalse($this->fetchRow($other->id()->toString()));
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $productId): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf(
                'SELECT label, unit_amount_in_cents FROM %s WHERE product_id = :productId',
                DbalListedProductProjector::TABLE,
            ),
            ['productId' => $productId],
        );
    }
}
