<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Infrastructure\Persistence\Projection\Finder;

use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Exception\ProductChangedException;
use Sales\Order\Application\Exception\ProductNotAvailableException;
use Sales\Order\Application\Finder\Product\ProductAvailabilityFinderInterface;
use Support\AbstractIntegrationTestCase;

final class DbalProductAvailabilityFinderTest extends AbstractIntegrationTestCase
{
    private ProductAvailabilityFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(ProductAvailabilityFinderInterface::class);
    }

    #[Test]
    public function itEnsuresAvailable(): void
    {
        // Given
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->store();

        // When
        $this->finder->ensureAvailable($product->id()->toString(), 'Espresso cups, set of 6', 1_750);

        // Then
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function itThrowsWhenTheProductDoesNotExist(): void
    {
        // Then
        $this->expectException(ProductNotAvailableException::class);

        // When
        $this->finder->ensureAvailable(Uuid::uuid7()->toString(), 'Ghost mug', 500);
    }

    #[Test]
    public function itThrowsWhenTheProductHasBeenDelisted(): void
    {
        // Given
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->delisted()->store();

        // Then
        $this->expectException(ProductNotAvailableException::class);

        // When
        $this->finder->ensureAvailable($product->id()->toString(), 'Espresso cups, set of 6', 1_750);
    }

    #[Test]
    public function itThrowsWhenThePriceHasChanged(): void
    {
        // Given
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->store();

        // Then
        $this->expectException(ProductChangedException::class);

        // When
        $this->finder->ensureAvailable($product->id()->toString(), 'Espresso cups, set of 6', 1_500);
    }

    #[Test]
    public function itThrowsWhenTheLabelHasChanged(): void
    {
        // Given
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->store();

        // Then
        $this->expectException(ProductChangedException::class);

        // When
        $this->finder->ensureAvailable($product->id()->toString(), 'Espresso cups, set of 12', 1_750);
    }
}
