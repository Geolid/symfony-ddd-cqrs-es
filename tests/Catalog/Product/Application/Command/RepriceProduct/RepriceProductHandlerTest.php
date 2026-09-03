<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\Command\RepriceProduct;

use Catalog\Product\Application\Command\RepriceProduct\RepriceProduct;
use Catalog\Product\Application\Finder\Product\ProductFinderInterface;
use Catalog\Product\Domain\Exception\ProductNotFoundException;
use Catalog\Product\Domain\ValueObject\ProductId;
use Catalog\Tests\Product\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class RepriceProductHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itReprices(): void
    {
        // Given
        $unitAmountInCents = ProductBuilder::sample('unitAmount')->cents;
        $newUnitAmountInCents = $unitAmountInCents + 100;

        $product = ProductBuilder::new()->withUnitAmountInCents($unitAmountInCents)->create();
        $this->store($product);

        // When
        $this->dispatch(new RepriceProduct($product->id->toString(), $newUnitAmountInCents));

        // Then
        $result = $this->service(ProductFinderInterface::class)->ofId($product->id->toString());
        self::assertSame($newUnitAmountInCents, $result->unitAmountInCents);
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
