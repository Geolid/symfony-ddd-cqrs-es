<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Infrastructure\Projection\Finder;

use Catalog\Listing\Application\Finder\Product\Exception\ProductResultNotFoundException;
use Catalog\Listing\Application\Finder\Product\ProductFinderInterface;
use Catalog\Listing\Application\Finder\Product\ProductResult;
use Catalog\Listing\Domain\Product;
use Catalog\Tests\Listing\Support\Factory\ProductFactory;
use Catalog\Tests\Listing\Support\Factory\ProductIdFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;
use Shared\Tests\Support\TestCase\RealColumnLeadsTrait;
use Symfony\Component\Clock\Clock;

/**
 * @extends AbstractIterableFinderTestCase<ProductResult>
 */
final class DbalProductFinderTest extends AbstractIterableFinderTestCase
{
    use RealColumnLeadsTrait;

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = ProductFactory::new()->create();
        $product = ProductFactory::new()->create();
        $this->store($other, $product);

        // When
        $result = $this->finder()->ofId($product->id->toString());

        // Then
        self::assertSame($product->id->toString(), $result->id);
        self::assertSame($product->label->value, $result->label);
        self::assertSame($product->unitPrice->cents, $result->unitPriceInCents);
        self::assertSameDateTime($product->listedAt, $result->listedAt);
        self::assertNull($result->repricedAt);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Given
        $unknownId = ProductIdFactory::new()->create()->toString();

        // Then
        $this->expectException(ProductResultNotFoundException::class);

        // When
        $this->finder()->ofId($unknownId);
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
        $products = ProductFactory::new()->many($count)->create();
        $this->store(...$products);

        return array_map(static fn (Product $product): string => $product->id->toString(), $products);
    }

    protected function indexOf(object $result): string
    {
        return $result->id;
    }

    /**
     * @return array{string, string}
     */
    protected function seedConflictingOrder(): array
    {
        $now = Clock::get()->now();
        $smallerId = Uuid::uuid7($now)->toString();
        $largerId = Uuid::uuid7($now->modify('+1 hour'))->toString();

        $first = ProductFactory::new()->withId(ProductIdFactory::new()->withId($largerId)->create())->withListedAt($now)->create();
        $second = ProductFactory::new()->withId(ProductIdFactory::new()->withId($smallerId)->create())->withListedAt($now->modify('+1 hour'))->create();
        $this->store($first, $second);

        return [$largerId, $smallerId];
    }
}
