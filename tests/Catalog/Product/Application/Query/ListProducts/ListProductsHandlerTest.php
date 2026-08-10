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
        $this->store($listed);
        $this->store(ProductTestFactory::new()->withLabel('Saucer')->delisted()->create());

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
        $this->store(ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->create());
        $this->store(ProductTestFactory::new()->withLabel('Saucer')->withUnitAmountInCents(990)->delisted()->create());

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
        // Labels are picked to sort predictably (ListProducts orders by label ASC) — 5 items, 2 per page, 3 pages.
        foreach (['Product 01', 'Product 02', 'Product 03', 'Product 04', 'Product 05'] as $label) {
            $this->store(ProductTestFactory::new()->withLabel($label)->create());
        }

        // When
        $firstPage = $this->ask(new ListProducts(page: 1, itemsPerPage: 2));
        $secondPage = $this->ask(new ListProducts(page: 2, itemsPerPage: 2));
        $lastPage = $this->ask(new ListProducts(page: 3, itemsPerPage: 2));

        // Then
        self::assertSame(['Product 01', 'Product 02'], array_map(static fn ($item) => $item->label, $firstPage->items));
        self::assertSame(5, $firstPage->pagination->totalItems);
        self::assertSame(1, $firstPage->pagination->currentPage);
        self::assertSame(2, $firstPage->pagination->itemsPerPage);
        self::assertSame(3, $firstPage->pagination->lastPage);

        self::assertSame(['Product 03', 'Product 04'], array_map(static fn ($item) => $item->label, $secondPage->items));
        self::assertSame(2, $secondPage->pagination->currentPage);

        self::assertSame(['Product 05'], array_map(static fn ($item) => $item->label, $lastPage->items));
        self::assertSame(3, $lastPage->pagination->currentPage);
    }
}
