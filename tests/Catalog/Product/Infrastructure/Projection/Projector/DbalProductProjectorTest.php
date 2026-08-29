<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Infrastructure\Projection\Projector;

use Catalog\Product\Infrastructure\Projection\Projector\DbalProductProjector;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Row array{label: string, unit_amount_in_cents: int, listed_at: string, repriced_at: string|null}
 */
final class DbalProductProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnProductListed(): void
    {
        // Given
        $listedAt = Clock::get()->now();
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->withListedAt($listedAt)->create();

        // When
        $this->store($product);

        // Then
        $row = $this->fetchRow($product->id->toString());
        self::assertNotFalse($row);
        self::assertSame('Espresso cups, set of 6', $row['label']);
        self::assertSame(1_750, (int) $row['unit_amount_in_cents']);
        self::assertSame($listedAt->format('Y-m-d H:i:s'), $row['listed_at']);
        self::assertNull($row['repriced_at']);
    }

    #[Test]
    public function itProjectsOnProductRepriced(): void
    {
        // Given
        $other = ProductTestFactory::new()->withUnitAmountInCents(500)->create();
        $this->store($other);

        // When
        $repricedAt = Clock::get()->now();
        $product = ProductTestFactory::new()->withUnitAmountInCents(1_750)->repriced(1_950, $repricedAt)->create();
        $this->store($product);

        // Then
        $row = $this->fetchRow($product->id->toString());
        self::assertNotFalse($row);
        self::assertSame(1_950, (int) $row['unit_amount_in_cents']);
        self::assertSame($repricedAt->format('Y-m-d H:i:s'), $row['repriced_at']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame(500, (int) $otherRow['unit_amount_in_cents']);
        self::assertNull($otherRow['repriced_at']);
    }

    #[Test]
    public function itRemovesOnProductDelisted(): void
    {
        // Given
        $other = ProductTestFactory::new()->create();
        $this->store($other);

        // When
        $product = ProductTestFactory::new()->delisted()->create();
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
        /** @var Row|false */
        return $this->serviceAs('doctrine.dbal.read_model_connection', Connection::class)->fetchAssociative(
            \sprintf('SELECT label, unit_amount_in_cents, listed_at, repriced_at FROM %s WHERE id = :id', DbalProductProjector::TABLE),
            ['id' => $id],
        );
    }
}
