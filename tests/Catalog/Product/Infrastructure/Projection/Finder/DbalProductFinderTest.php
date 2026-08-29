<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Infrastructure\Projection\Finder;

use Catalog\Product\Application\Exception\ProductResultNotFoundException;
use Catalog\Product\Application\Finder\Product\ProductFinderInterface;
use Catalog\Product\Application\Finder\Product\ProductResult;
use Catalog\Product\Domain\Product;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
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
    public function itGetsById(): void
    {
        // Given
        $other = ProductTestFactory::new()->withLabel('Saucer')->create();
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->create();
        $this->store($other, $product);

        // When
        $result = $this->finder->ofId($product->id->toString());

        // Then
        self::assertSame($product->id->toString(), $result->id);
        self::assertSame('Espresso cups, set of 6', $result->label);
        self::assertSame(1_750, $result->unitAmountInCents);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(ProductResultNotFoundException::class);

        // When
        $this->finder->ofId(Uuid::uuid7()->toString());
    }

    #[Test]
    public function itLists(): void
    {
        // Given
        $other = ProductTestFactory::new()->withLabel('Saucer')->delisted()->create();
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->create();
        $this->store($other, $product);

        // When
        $results = iterator_to_array($this->finder);

        // Then
        self::assertCount(1, $results);
        $result = $results[0];
        self::assertInstanceOf(ProductResult::class, $result);
        self::assertSame($product->id->toString(), $result->id);
        self::assertSame('Espresso cups, set of 6', $result->label);
        self::assertSame(1_750, $result->unitAmountInCents);
    }

    #[Test]
    public function itListsWhenEmpty(): void
    {
        // When
        $results = iterator_to_array($this->finder);

        // Then
        self::assertEmpty($results);
    }

    #[Test]
    public function itListsSortedByLabel(): void
    {
        // Given
        $zebraMug = ProductTestFactory::new()->withLabel('Zebra mug')->create();
        $appleCrate = ProductTestFactory::new()->withLabel('Apple crate')->create();
        $this->store($zebraMug, $appleCrate);

        // When
        $results = iterator_to_array($this->finder->sortedByLabel());

        // Then
        self::assertSame(
            ['Apple crate', 'Zebra mug'],
            array_map(static fn (ProductResult $result): string => $result->label, $results),
        );
    }

    #[Test]
    public function itPaginates(): void
    {
        // Given
        $products = ProductTestFactory::new()->many(5)->create();
        $this->store(...$products);

        // When
        $firstPage = $this->finder->paginate(page: 1, itemsPerPage: 2);
        $secondPage = $this->finder->paginate(page: 2, itemsPerPage: 2);
        $lastPage = $this->finder->paginate(page: 3, itemsPerPage: 2);
        $outOfBoundsPage = $this->finder->paginate(page: 4, itemsPerPage: 2);

        // Then
        self::assertSame($this->productIds($products[0], $products[1]), $this->resultIds($firstPage));
        self::assertSame($this->productIds($products[2], $products[3]), $this->resultIds($secondPage));
        self::assertSame($this->productIds($products[4]), $this->resultIds($lastPage));
        self::assertCount(0, $outOfBoundsPage);

        self::assertSame(5, $firstPage->totalItems());
        self::assertSame(3, $firstPage->lastPage());
        self::assertSame(1, $firstPage->currentPage());
        self::assertSame(2, $firstPage->itemsPerPage());
        self::assertSame(2, $secondPage->currentPage());
        self::assertSame(3, $lastPage->currentPage());
        self::assertSame(4, $outOfBoundsPage->currentPage());
    }

    #[Test]
    public function itPaginatesWhenEmpty(): void
    {
        // When
        $paginator = $this->finder->paginate(page: 1, itemsPerPage: 20);

        // Then
        self::assertCount(0, $paginator);
        self::assertSame(0, $paginator->totalItems());
        self::assertSame(1, $paginator->lastPage());
    }

    /**
     * @return list<string>
     */
    private function productIds(Product ...$products): array
    {
        $ids = [];
        foreach ($products as $product) {
            $ids[] = $product->id->toString();
        }

        return $ids;
    }

    /**
     * @param iterable<ProductResult> $results
     *
     * @return list<string>
     */
    private function resultIds(iterable $results): array
    {
        $ids = [];
        foreach ($results as $result) {
            $ids[] = $result->id;
        }

        return $ids;
    }
}
