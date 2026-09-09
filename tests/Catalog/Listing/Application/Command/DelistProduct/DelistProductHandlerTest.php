<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Application\Command\DelistProduct;

use Catalog\Listing\Application\Command\DelistProduct\DelistProduct;
use Catalog\Listing\Application\Finder\Product\Exception\ProductResultNotFoundException;
use Catalog\Listing\Application\Finder\Product\ProductFinderInterface;
use Catalog\Listing\Application\Uniqueness\ProductUniqueKey;
use Catalog\Listing\Domain\Exception\ProductNotFoundException;
use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class DelistProductHandlerTest extends AbstractIntegrationTestCase
{
    private UniqueValueRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uniqueValues = $this->service(UniqueValueRegistryInterface::class);
    }

    #[Test]
    public function itDelists(): void
    {
        // Given
        $builder = ProductBuilder::new();
        $product = $builder->create();
        $this->store($product);
        $labelKey = UniqueKey::for(ProductUniqueKey::LABEL);
        $this->uniqueValues->reserve($labelKey, $builder['label']->value, $product->id->toString());

        // When
        $this->dispatch(new DelistProduct($product->id->toString()));

        // Then
        self::assertFalse($this->uniqueValues->exists($labelKey, $builder['label']->value));
        $this->expectException(ProductResultNotFoundException::class);

        $this->service(ProductFinderInterface::class)->ofId($product->id->toString());
    }

    #[Test]
    public function itIgnoresWhenAlreadyDelisted(): void
    {
        // Given
        $product = ProductBuilder::new()->delisted()->create();
        $this->store($product);

        // When
        $this->dispatch(new DelistProduct($product->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(ProductNotFoundException::class);

        // When
        $this->dispatch(new DelistProduct(Uuid::uuid7()->toString()));
    }
}
