<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Infrastructure\Persistence\Projection\Finder;

use Catalog\Product\Application\Finder\Product\ProductFinderInterface;
use Catalog\Product\Application\Finder\Product\ProductResult;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class DbalProductFinderTest extends AbstractIntegrationTestCase
{
    private ProductFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(ProductFinderInterface::class);
    }

    #[Test]
    public function itListsProducts(): void
    {
        // Given
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->create();
        $this->store($product);

        // When
        $results = iterator_to_array($this->finder);

        // Then
        self::assertCount(1, $results);
        $result = $results[0];
        self::assertInstanceOf(ProductResult::class, $result);
        self::assertSame($product->id()->toString(), $result->id);
        self::assertSame('Espresso cups, set of 6', $result->label);
        self::assertSame(1_750, $result->unitAmountInCents);
        self::assertFalse($result->delisted);
    }

    #[Test]
    public function itListsProductsSortedByLabel(): void
    {
        // Given
        $zebra = ProductTestFactory::new()->withLabel('Zebra mug')->create();
        $apple = ProductTestFactory::new()->withLabel('Apple crate')->create();
        $this->store($zebra, $apple);

        // When
        $results = iterator_to_array($this->finder->sortedByLabel());

        // Then
        self::assertSame(
            ['Apple crate', 'Zebra mug'],
            array_map(static fn (ProductResult $result): string => $result->label, $results),
        );
    }

    #[Test]
    public function itFiltersProductsByDelisting(): void
    {
        // Given
        $listed = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->create();
        $this->store($listed, ProductTestFactory::new()->delisted()->create());

        // When
        $results = iterator_to_array($this->finder->withoutDelisted());

        // Then
        self::assertSame(2, \count($this->finder));
        self::assertCount(1, $results);
        $result = $results[0];
        self::assertSame($listed->id()->toString(), $result->id);
        self::assertSame('Espresso cups, set of 6', $result->label);
        self::assertSame(1_750, $result->unitAmountInCents);
        self::assertFalse($result->delisted);
    }
}
