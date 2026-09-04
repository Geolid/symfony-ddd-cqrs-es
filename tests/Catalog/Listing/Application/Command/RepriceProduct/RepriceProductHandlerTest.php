<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Application\Command\RepriceProduct;

use Catalog\Listing\Application\Command\RepriceProduct\RepriceProduct;
use Catalog\Listing\Application\Finder\Product\ProductFinderInterface;
use Catalog\Listing\Domain\Exception\ProductNotFoundException;
use Catalog\Listing\Domain\ValueObject\ProductId;
use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class RepriceProductHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itReprices(): void
    {
        // Given
        $unitPriceInCents = ProductBuilder::sample('unitAmount')->cents;
        $newUnitPriceInCents = $unitPriceInCents + 100;

        $product = ProductBuilder::new()->withUnitPriceInCents($unitPriceInCents)->create();
        $this->store($product);

        // When
        $this->dispatch(new RepriceProduct($product->id->toString(), $newUnitPriceInCents));

        // Then
        $result = $this->service(ProductFinderInterface::class)->ofId($product->id->toString());
        self::assertSame($newUnitPriceInCents, $result->unitPriceInCents);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(ProductNotFoundException::class);

        // When
        $this->dispatch(new RepriceProduct(
            ProductId::generate()->toString(),
            ProductBuilder::sample('unitAmount')->cents,
        ));
    }
}
