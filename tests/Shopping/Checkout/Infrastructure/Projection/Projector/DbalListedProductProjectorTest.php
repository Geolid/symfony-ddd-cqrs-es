<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\Projection\Projector;

use Catalog\Tests\Listing\Support\Factory\ProductFactory;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\ValueObject\Money;
use Shopping\Checkout\Infrastructure\Projection\Projector\DbalListedProductProjector;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Row array{label: string, unit_price_in_cents: int|string}
 */
final class DbalListedProductProjectorTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itProjectsOnProductListed(): void
    {
        // Given
        $product = ProductFactory::new()->create();

        // When
        $this->store($product);

        // Then
        $row = $this->fetchRow($product->id->toString());
        self::assertNotFalse($row);
        self::assertSame($product->label->value, $row['label']);
        self::assertSame($product->unitPrice->cents, (int) $row['unit_price_in_cents']);
    }

    #[Test]
    public function itProjectsOnProductRepriced(): void
    {
        // Given
        $other = ProductFactory::new()->create();
        $product = ProductFactory::new()->create();
        $this->store($other, $product);

        // When
        $product->reprice(Money::fromCents(10_000, 'EUR'), Clock::get()->now());
        $this->store($product);

        // Then
        $row = $this->fetchRow($product->id->toString());
        self::assertNotFalse($row);
        self::assertSame(10_000, (int) $row['unit_price_in_cents']);

        $otherRow = $this->fetchRow($other->id->toString());
        self::assertNotFalse($otherRow);
        self::assertSame($other->unitPrice->cents, (int) $otherRow['unit_price_in_cents']);
    }

    #[Test]
    public function itRemovesOnProductDelisted(): void
    {
        // Given
        $other = ProductFactory::new()->create();
        $this->store($other);
        $product = ProductFactory::new()->delisted()->create();

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
