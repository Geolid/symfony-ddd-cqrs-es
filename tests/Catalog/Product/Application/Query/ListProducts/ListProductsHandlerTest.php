<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\Query\ListProducts;

use Catalog\Product\Application\Query\ListProducts\ListProducts;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class ListProductsHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itListsProducts(): void
    {
        // Given
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->create();
        $this->store($product);

        // When
        $result = $this->ask(new ListProducts());

        // Then
        self::assertCount(1, $result->items);
        self::assertSame($product->id()->toString(), $result->items[0]->id);
        self::assertSame('Espresso cups, set of 6', $result->items[0]->label);
        self::assertSame(1_750, $result->items[0]->unitAmountInCents);
        self::assertFalse($result->items[0]->delisted);
        self::assertSame(1, $result->pagination->totalItems);
        self::assertSame(1, $result->pagination->currentPage);
        self::assertSame(20, $result->pagination->itemsPerPage);
        self::assertSame(1, $result->pagination->lastPage);
    }

    #[Test]
    public function itListsProductsExcludingDelistedByDefault(): void
    {
        // Given
        $listed = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->create();
        $this->store($listed, ProductTestFactory::new()->withLabel('Saucer')->delisted()->create());

        // When
        $result = $this->ask(new ListProducts());

        // Then
        self::assertCount(1, $result->items);
        self::assertSame($listed->id()->toString(), $result->items[0]->id);
        self::assertSame('Espresso cups, set of 6', $result->items[0]->label);
        self::assertSame(1_750, $result->items[0]->unitAmountInCents);
        self::assertFalse($result->items[0]->delisted);
    }

    #[Test]
    public function itListsDelistedProductsWhenAsked(): void
    {
        // Given
        $this->store(
            ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->create(),
            ProductTestFactory::new()->withLabel('Saucer')->withUnitAmountInCents(990)->delisted()->create(),
        );

        // When
        $result = $this->ask(new ListProducts(includeDelisted: true));

        // Then
        self::assertCount(2, $result->items);
        self::assertSame('Espresso cups, set of 6', $result->items[0]->label);
        self::assertSame(1_750, $result->items[0]->unitAmountInCents);
        self::assertFalse($result->items[0]->delisted);
        self::assertSame('Saucer', $result->items[1]->label);
        self::assertSame(990, $result->items[1]->unitAmountInCents);
        self::assertTrue($result->items[1]->delisted);
    }

    #[Test]
    public function itPaginatesProducts(): void
    {
        // Given
        // Pagination math, not values — 5 arbitrary products, 2 per page, 3 pages.
        $this->store(...ProductTestFactory::createMany(5));

        // When
        $firstPage = $this->ask(new ListProducts(page: 1, itemsPerPage: 2));
        $secondPage = $this->ask(new ListProducts(page: 2, itemsPerPage: 2));
        $lastPage = $this->ask(new ListProducts(page: 3, itemsPerPage: 2));

        // Then
        self::assertCount(2, $firstPage->items);
        self::assertSame(5, $firstPage->pagination->totalItems);
        self::assertSame(1, $firstPage->pagination->currentPage);
        self::assertSame(2, $firstPage->pagination->itemsPerPage);
        self::assertSame(3, $firstPage->pagination->lastPage);

        self::assertCount(2, $secondPage->items);
        self::assertSame(2, $secondPage->pagination->currentPage);

        self::assertCount(1, $lastPage->items);
        self::assertSame(3, $lastPage->pagination->currentPage);
    }
}
