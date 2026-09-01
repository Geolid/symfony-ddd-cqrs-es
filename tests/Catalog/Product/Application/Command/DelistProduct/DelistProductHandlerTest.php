<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\Command\DelistProduct;

use Catalog\Product\Application\Command\DelistProduct\DelistProduct;
use Catalog\Product\Application\Exception\ProductResultNotFoundException;
use Catalog\Product\Application\Finder\Product\ProductFinderInterface;
use Catalog\Product\Domain\Exception\ProductNotFoundException;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class DelistProductHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDelists(): void
    {
        // Given
        $product = ProductTestFactory::new()->create();
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
        $product = ProductTestFactory::new()->delisted()->create();
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
        $id = ProductTestFactory::new()->attribute('id')->toString();

        // Then
        $this->expectException(ProductNotFoundException::class);

        // When
        $this->dispatch(new DelistProduct($id));
    }
}
