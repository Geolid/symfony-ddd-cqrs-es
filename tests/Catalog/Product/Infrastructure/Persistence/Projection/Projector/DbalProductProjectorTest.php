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
    public function itProjectsTheProductOnProductListed(): void
    {
        // When
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->create();
        $this->store($product);

        // Then
        $row = $this->fetchRow($product->id()->toString());
        self::assertNotFalse($row);
        self::assertSame('Espresso cups, set of 6', $row['label']);
        self::assertSame(1_750, (int) $row['unit_amount_in_cents']);
    }

    #[Test]
    public function itProjectsTheNewPriceOnProductRepriced(): void
    {
        // When
        $product = ProductTestFactory::new()->withUnitAmountInCents(1_750)->repriced(1_950)->create();
        $this->store($product);

        // Then
        $row = $this->fetchRow($product->id()->toString());
        self::assertNotFalse($row);
        self::assertSame(1_950, (int) $row['unit_amount_in_cents']);
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
