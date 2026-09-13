<?php

declare(strict_types=1);

namespace Shopping\Tests\Cart\Infrastructure\Projection\Finder;

use Catalog\Listing\Domain\Product;
use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;
use Shopping\Cart\Application\Finder\ListedProduct\ListedProductFinderInterface;
use Shopping\Cart\Application\Finder\ListedProduct\ListedProductResult;

/**
 * @extends AbstractIterableFinderTestCase<ListedProductResult>
 */
final class DbalListedProductFinderTest extends AbstractIterableFinderTestCase
{
    #[Test]
    public function itFiltersByIds(): void
    {
        // Given
        $other = ProductBuilder::new()->create();
        $label = ProductBuilder::sample('label');
        $unitPrice = ProductBuilder::sample('unitPrice');
        $cups = ProductBuilder::new()->withLabel($label->value)->withUnitPriceInCents($unitPrice->cents)->create();
        $this->store($other, $cups);

        // When
        $results = iterator_to_array($this->finder()->byIds($cups->id->toString(), Uuid::uuid7()->toString()));

        // Then
        self::assertCount(1, $results);
        self::assertSame($cups->id->toString(), $results[0]->productId);
        self::assertSame($label->value, $results[0]->label);
        self::assertSame($unitPrice->cents, $results[0]->unitPriceInCents);
    }

    protected function finder(): ListedProductFinderInterface
    {
        return $this->service(ListedProductFinderInterface::class);
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
        return $result->productId;
    }
}
