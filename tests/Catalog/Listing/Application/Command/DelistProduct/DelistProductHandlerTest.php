<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Application\Command\DelistProduct;

use Catalog\Listing\Application\Command\DelistProduct\DelistProduct;
use Catalog\Listing\Application\Finder\Product\Exception\ProductResultNotFoundException;
use Catalog\Listing\Application\Finder\Product\ProductFinderInterface;
use Catalog\Listing\Application\ListingUniqueKey;
use Catalog\Listing\Domain\Exception\ProductNotFoundException;
use Catalog\Tests\Listing\Support\Factory\ProductFactory;
use Catalog\Tests\Listing\Support\Factory\ProductIdFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class DelistProductHandlerTest extends AbstractIntegrationTestCase
{
    private UniquenessRegistryInterface $uniqueness;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uniqueness = $this->service(UniquenessRegistryInterface::class);
    }

    #[Test]
    public function itDelists(): void
    {
        // Given
        $product = ProductFactory::new()->create();
        $this->store($product);
        $labelKey = UniqueKey::for(ListingUniqueKey::LABEL);
        $this->uniqueness->claim($labelKey, $product->label->value, $product->id->toString());

        // When
        $this->dispatch(new DelistProduct($product->id->toString()));

        // Then
        self::assertFalse($this->uniqueness->isClaimed($labelKey, $product->label->value));
        $this->expectException(ProductResultNotFoundException::class);

        $this->service(ProductFinderInterface::class)->ofId($product->id->toString());
    }

    #[Test]
    public function itIgnoresWhenAlreadyDelisted(): void
    {
        // Given
        $product = ProductFactory::new()->delisted()->create();
        $this->store($product);

        // When
        $this->dispatch(new DelistProduct($product->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = ProductIdFactory::new()->create()->toString();

        // Then
        $this->expectException(ProductNotFoundException::class);

        // When
        $this->dispatch(new DelistProduct($id));
    }
}
