<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Application\Query\ListProducts;

use Catalog\Listing\Application\Finder\Product\ProductResult;
use Catalog\Listing\Application\Query\ListProducts\ListProducts;
use Catalog\Listing\Domain\Product;
use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Finder\PaginationMetadata;
use Shared\Application\Query\Result\PaginatedResult;
use Shared\Tests\Support\PaginationTrait;
use Support\TestCase\AbstractIntegrationTestCase;

final class ListProductsHandlerTest extends AbstractIntegrationTestCase
{
    /** @use PaginationTrait<PaginatedResult<ProductResult>> */
    use PaginationTrait;

    #[Test]
    public function itPaginates(): void
    {
        // Given
        $builder = ProductBuilder::new();
        $product = $builder->create();

        $others = ProductBuilder::new()->many(4)->create();

        $products = [$product, ...$others];
        $this->store(...$products);

        // When
        $pages = $this->traversePages(
            expectedIds: array_map(static fn (Product $eachProduct): string => $eachProduct->id->toString(), $products),
            pageSize: 2,
            askPage: $this->askPage(...),
            idsOf: $this->idsOf(...),
            metadataOf: $this->metadataOf(...),
        );

        // Then
        [$productResult] = $pages[1]->items;

        self::assertSame($product->id->toString(), $productResult->id);
        self::assertSame($builder['label']->value, $productResult->label);
        self::assertSame($builder['unitPrice']->cents, $productResult->unitPriceInCents);
    }

    #[Test]
    public function itPaginatesWhenEmpty(): void
    {
        // When
        $this->traverseEmptyPage(
            askPage: $this->askPage(...),
            idsOf: $this->idsOf(...),
            metadataOf: $this->metadataOf(...),
            itemsPerPage: 20,
        );
    }

    /**
     * @return PaginatedResult<ProductResult>
     */
    private function askPage(int $page, int $itemsPerPage): PaginatedResult
    {
        return $this->ask(new ListProducts($page, $itemsPerPage));
    }

    /**
     * @param PaginatedResult<ProductResult> $result
     *
     * @return list<string>
     */
    private function idsOf(PaginatedResult $result): array
    {
        return array_map(static fn (ProductResult $item): string => $item->id, $result->items);
    }

    /**
     * @param PaginatedResult<ProductResult> $result
     */
    private function metadataOf(PaginatedResult $result): PaginationMetadata
    {
        return $result->pagination;
    }
}
