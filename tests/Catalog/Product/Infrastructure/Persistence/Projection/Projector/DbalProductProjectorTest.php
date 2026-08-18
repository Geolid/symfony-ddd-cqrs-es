<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Infrastructure\Persistence\Projection\Projector;

use Catalog\Product\Infrastructure\Persistence\Projection\Projector\DbalProductProjector;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

/**
 * @phpstan-type Row array{label: string, unit_amount_in_cents: int}
 */
final class DbalProductProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnProductListed(): void
    {
        // When
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->store();

        // Then
        $row = $this->fetchRow($product->id->toString());
        self::assertNotFalse($row);
        self::assertSame('Espresso cups, set of 6', $row['label']);
        self::assertSame(1_750, (int) $row['unit_amount_in_cents']);
    }

    #[Test]
    public function itProjectsTheNewPriceOnProductRepriced(): void
    {
        // Given
        $other = ProductTestFactory::new()->withUnitAmountInCents(500)->store();

        // When
        $product = ProductTestFactory::new()->withUnitAmountInCents(1_750)->repriced(1_950)->store();

        // Then
        $row = $this->fetchRow($product->id->toString());
        self::assertNotFalse($row);
        self::assertSame(1_950, (int) $row['unit_amount_in_cents']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(500, (int) $otherRow['unit_amount_in_cents']);
    }

    #[Test]
    public function itDeletesOnProductDelisted(): void
    {
        // When
        $product = ProductTestFactory::new()->delisted()->store();

        // Then
        self::assertFalse($this->fetchRow($product->id->toString()));
    }

    /**
     * @return Row|false
     */
    private function fetchRow(string $id): array|false
    {
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf('SELECT label, unit_amount_in_cents FROM %s WHERE id = :id', DbalProductProjector::TABLE),
            ['id' => $id],
        );
    }
}
