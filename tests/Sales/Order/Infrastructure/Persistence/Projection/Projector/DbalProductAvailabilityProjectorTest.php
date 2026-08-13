<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\Projection\Projector;

use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Sales\Order\Infrastructure\Persistence\Projection\Projector\DbalProductAvailabilityProjector;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{label: string, unit_amount_in_cents: int|string}
 */
final class DbalProductAvailabilityProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsANewProductOnProductListed(): void
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
    public function itProjectsTheNewPriceOnProductRepriced(): void
    {
        // Given
        $product = ProductTestFactory::new()->withUnitAmountInCents(1_750)->repriced(2_000)->create();

        // When
        $this->store($product);

        // Then
        $row = $this->fetchRow($product->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(2_000, (int) $row['unit_amount_in_cents']);
    }

    #[Test]
    public function itRemovesTheProductOnProductDelisted(): void
    {
        // Given
        $product = ProductTestFactory::new()->delisted()->create();

        // When
        $this->store($product);

        // Then
        self::assertFalse($this->fetchRow($product->id()->toString()));
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
                DbalProductAvailabilityProjector::TABLE,
            ),
            ['productId' => $productId],
        );
    }
}
