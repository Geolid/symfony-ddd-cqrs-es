<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Application\Command\DelistProduct;

use Catalog\Listing\Application\Command\DelistProduct\DelistProduct;
use Catalog\Listing\Application\Exception\ProductResultNotFoundException;
use Catalog\Listing\Application\Finder\Product\ProductFinderInterface;
use Catalog\Listing\Domain\Exception\ProductNotFoundException;
use Catalog\Listing\Domain\ValueObject\ProductId;
use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class DelistProductHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDelists(): void
    {
        // Given
        $product = ProductBuilder::new()->create();
        $this->store($product);

        // When
        $this->dispatch(new DelistProduct($product->id->toString()));

        // Then
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
        $this->dispatch(new DelistProduct(ProductId::generate()->toString()));
    }
}
