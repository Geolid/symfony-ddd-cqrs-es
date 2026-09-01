<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\Command\RepriceProduct;

use Catalog\Product\Application\Command\RepriceProduct\RepriceProduct;
use Catalog\Product\Application\Finder\Product\ProductFinderInterface;
use Catalog\Product\Domain\Exception\ProductNotFoundException;
use Catalog\Tests\Product\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class RepriceProductHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itReprices(): void
    {
        // Given
        $product = ProductBuilder::new()->create();
        $this->store($product);

        // When
        $this->dispatch(new RepriceProduct($product->id->toString(), 1_950));

        // Then
        $result = $this->service(ProductFinderInterface::class)->ofId($product->id->toString());
        self::assertSame(1_950, $result->unitAmountInCents);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = ProductBuilder::new()->attribute('id')->toString();
        $price = ProductBuilder::new()->attribute('unitAmount')->cents;

        // Then
        $this->expectException(ProductNotFoundException::class);

        // When
        $this->dispatch(new RepriceProduct($id, $price));
    }
}
