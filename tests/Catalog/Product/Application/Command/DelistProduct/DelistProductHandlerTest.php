<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\Command\DelistProduct;

use Catalog\Product\Application\Command\DelistProduct\DelistProduct;
use Catalog\Product\Application\Finder\Product\ProductFinderInterface;
use Catalog\Product\Domain\Exception\ProductAlreadyDelistedException;
use Catalog\Product\Domain\Exception\ProductNotFoundException;
use Catalog\Product\Domain\ValueObject\ProductId;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class DelistProductHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDelistsAProduct(): void
    {
        // Given
        $product = ProductTestFactory::new()->create();
        $this->store($product);

        // When
        $this->dispatch(new DelistProduct($product->id()->toString()));

        // Then
        $result = $this->service(ProductFinderInterface::class)->ofId($product->id()->toString());
        self::assertTrue($result->delisted);
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
    public function itFailsWhenTheProductIsAlreadyDelisted(): void
    {
        // Given
        $product = ProductTestFactory::new()->delisted()->create();
        $this->store($product);

        // Then
        $this->expectException(ProductAlreadyDelistedException::class);

        // When
        $this->dispatch(new DelistProduct($product->id()->toString()));
    }
}
