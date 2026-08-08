<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\Command\RepriceProduct;

use Catalog\Product\Application\Command\ListProductForSale\ListProductForSale;
use Catalog\Product\Application\Command\RepriceProduct\RepriceProduct;
use Catalog\Product\Application\Finder\Product\ProductFinderInterface;
use Catalog\Product\Domain\Exception\ProductNotFoundException;
use Catalog\Product\Domain\ValueObject\ProductId;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class RepriceProductHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRepricesAProduct(): void
    {
        // Given
        $id = ProductId::generate()->toString();
        $this->dispatch(new ListProductForSale($id, 'Espresso cups, set of 6', 1_750));

        // When
        $this->dispatch(new RepriceProduct($id, 1_950));

        // Then
        $result = $this->service(ProductFinderInterface::class)->ofId($id);
        self::assertSame(1_950, $result->unitAmountInCents);
    }

    #[Test]
    public function itFailsWhenTheProductDoesNotExist(): void
    {
        // Then
        $this->expectException(ProductNotFoundException::class);

        // When
        $this->dispatch(new RepriceProduct(ProductId::generate()->toString(), 1_950));
    }
}
