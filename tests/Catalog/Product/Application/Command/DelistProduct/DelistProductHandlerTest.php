<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\Command\DelistProduct;

use Catalog\Product\Application\Command\DelistProduct\DelistProduct;
use Catalog\Product\Application\Finder\Product\ProductFinderInterface;
use Catalog\Product\Domain\ValueObject\ProductId;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\Exception\AggregateNotFoundException;
use Support\AbstractIntegrationTestCase;

final class DelistProductHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDelistsAProduct(): void
    {
        // Given
        $product = ProductTestFactory::new()->store();

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
        $this->expectException(AggregateNotFoundException::class);

        // When
        $this->dispatch(new DelistProduct($id));
    }

    #[Test]
    public function itIgnoresAnAlreadyDelistedProduct(): void
    {
        // Given
        $product = ProductTestFactory::new()->delisted()->store();

        // When
        $this->dispatch(new DelistProduct($product->id()->toString()));

        // Then
        self::expectNotToPerformAssertions();
    }
}
