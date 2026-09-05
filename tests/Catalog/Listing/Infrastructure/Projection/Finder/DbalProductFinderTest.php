<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Infrastructure\Projection\Finder;

use Catalog\Listing\Application\Finder\Product\Exception\ProductResultNotFoundException;
use Catalog\Listing\Application\Finder\Product\ProductFinderInterface;
use Catalog\Listing\Application\Finder\Product\ProductResult;
use Catalog\Listing\Domain\Product;
use Catalog\Listing\Domain\ValueObject\ProductId;
use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Finder\PaginationMetadata;
use Shared\Application\Finder\PaginatorInterface;
use Shared\Tests\Support\PaginationTrait;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;

/**
 * @extends AbstractIterableFinderTestCase<ProductResult>
 */
final class DbalProductFinderTest extends AbstractIterableFinderTestCase
{
    /** @use PaginationTrait<PaginatorInterface<ProductResult>> */
    use PaginationTrait;

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = ProductBuilder::new()->create();
        $builder = ProductBuilder::new();
        $product = $builder->create();
        $this->store($other, $product);

        // When
        $result = $this->finder()->ofId($product->id->toString());

        // Then
        self::assertSame($product->id->toString(), $result->id);
        self::assertSame($builder['label']->value, $result->label);
        self::assertSame($builder['unitPrice']->cents, $result->unitPriceInCents);
        self::assertSame(
            $builder['listedAt']->format(\DateTimeInterface::ATOM),
            $result->listedAt->format(\DateTimeInterface::ATOM),
        );
        self::assertNull($result->repricedAt);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(ProductResultNotFoundException::class);

        // When
        $this->finder()->ofId(ProductId::generate()->toString());
    }

    #[Test]
    public function itPaginates(): void
    {
        // Given
        $finder = $this->finder();
        $ids = $this->seed(5);

        // When
        $this->traversePages(
            expectedIds: $ids,
            pageSize: 2,
            askPage: static fn (int $page, int $itemsPerPage): PaginatorInterface => $finder->paginate($page, $itemsPerPage),
            idsOf: $this->resultIds(...),
            metadataOf: PaginationMetadata::fromPaginator(...),
        );
    }

    #[Test]
    public function itPaginatesWhenEmpty(): void
    {
        // Given
        $finder = $this->finder();

        // When
        $this->traverseEmptyPage(
            askPage: static fn (int $page, int $itemsPerPage): PaginatorInterface => $finder->paginate($page, $itemsPerPage),
            idsOf: $this->resultIds(...),
            metadataOf: PaginationMetadata::fromPaginator(...),
            itemsPerPage: 20,
        );
    }

    protected function finder(): ProductFinderInterface
    {
        return $this->service(ProductFinderInterface::class);
    }

    /**
     * @return list<string>
     */
    protected function seed(int $count): array
    {
        $products = ProductBuilder::new()->many($count)->create();
        $this->store(...$products);

        return array_map(static fn (Product $product): string => $product->id->toString(), $products);
    }

    protected function idOf(object $result): string
    {
        return $result->id;
    }
}
