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
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->create();
        $this->store($product);

        // When
        $result = $this->ask(new ListProducts());

        // Then
        self::assertCount(1, $result->items);
        self::assertSame($product->id()->toString(), $result->items[0]->id);
        self::assertSame(1, $result->pagination->totalItems);
        self::assertSame(1, $result->pagination->currentPage);
        self::assertSame(20, $result->pagination->itemsPerPage);
        self::assertSame(1, $result->pagination->lastPage);
    }

    #[Test]
    public function itListsProductsExcludingDelistedByDefault(): void
    {
        // Given
        $listed = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->create();
        $this->store($listed);
        $this->store(ProductTestFactory::new()->withLabel('Saucer')->delisted()->create());

        // When
        $result = $this->ask(new ListProducts());

        // Then
        self::assertCount(1, $result->items);
        self::assertSame($listed->id()->toString(), $result->items[0]->id);
    }

    #[Test]
    public function itListsDelistedProductsWhenAsked(): void
    {
        // Given
        $this->store(ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->create());
        $this->store(ProductTestFactory::new()->withLabel('Saucer')->delisted()->create());

        // When
        $result = $this->ask(new ListProducts(includeDelisted: true));

        // Then
        self::assertCount(2, $result->items);
    }

    #[Test]
    public function itPaginatesProducts(): void
    {
        // Given
        $this->store(ProductTestFactory::new()->withLabel('Saucer')->create());
        $first = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->create();
        $this->store($first);

        // When
        $result = $this->ask(new ListProducts(page: 1, itemsPerPage: 1));

        // Then
        self::assertCount(1, $result->items);
        self::assertSame($first->id()->toString(), $result->items[0]->id);
        self::assertSame(2, $result->pagination->totalItems);
        self::assertSame(2, $result->pagination->lastPage);
    }
}
