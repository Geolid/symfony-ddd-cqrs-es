<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\Command\DelistProduct;

use Catalog\Product\Application\Command\DelistProduct\DelistProduct;
use Catalog\Product\Application\Exception\ProductResultNotFoundException;
use Catalog\Product\Application\Finder\Product\ProductFinderInterface;
use Catalog\Product\Domain\Exception\ProductNotFoundException;
use Catalog\Product\Domain\ValueObject\ProductId;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class DelistProductHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDelists(): void
    {
        // Given
        $product = ProductTestFactory::new()->store();

        // Then
        $this->expectException(ProductResultNotFoundException::class);

        // When
        $this->dispatch(new DelistProduct($product->id->toString()));
        $this->service(ProductFinderInterface::class)->ofId($product->id->toString());
    }

    #[Test]
    public function itFailsWhenTheProductDoesNotExist(): void
    {
        // Given
        $id = ProductId::generate()->toString();

        // Then
        $this->expectException(ProductNotFoundException::class);

        // When
        $this->dispatch(new DelistProduct($id));
    }

    #[Test]
    public function itIgnoresAnAlreadyDelisted(): void
    {
        // Given
        $product = ProductTestFactory::new()->delisted()->store();

        // When
        $this->dispatch(new DelistProduct($product->id->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
