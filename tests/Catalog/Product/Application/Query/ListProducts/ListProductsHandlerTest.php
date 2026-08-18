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
    public function itLists(): void
    {
        // Given
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->store();
        ProductTestFactory::new()->withLabel('Saucer')->delisted()->store();

        // When
        $result = $this->ask(new ListProducts());

        // Then
        self::assertCount(1, $result->items);
        self::assertSame($product->id->toString(), $result->items[0]->id);
        self::assertSame('Espresso cups, set of 6', $result->items[0]->label);
        self::assertSame(1_750, $result->items[0]->unitAmountInCents);
        self::assertSame(1, $result->pagination->totalItems);
        self::assertSame(1, $result->pagination->currentPage);
        self::assertSame(20, $result->pagination->itemsPerPage);
        self::assertSame(1, $result->pagination->lastPage);
    }

    #[Test]
    public function itPaginates(): void
    {
        // Given
        ProductTestFactory::new()->many(5)->store();

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
