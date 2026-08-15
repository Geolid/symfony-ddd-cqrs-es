<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\Command\RepriceProduct;

use Catalog\Product\Application\Command\RepriceProduct\RepriceProduct;
use Catalog\Product\Application\Finder\Product\ProductFinderInterface;
use Catalog\Product\Domain\Exception\ProductNotFoundException;
use Catalog\Product\Domain\ValueObject\ProductId;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class RepriceProductHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRepricesAProduct(): void
    {
        // Given
        $id = ProductId::generate()->toString();
        ProductTestFactory::new()->withId($id)->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->store();

        // When
        $this->dispatch(new RepriceProduct($id, 1_950));

        // Then
        $result = $this->service(ProductFinderInterface::class)->ofId($id);
        self::assertSame(1_950, $result->unitAmountInCents);
    }

    #[Test]
    public function itFailsWhenTheProductDoesNotExist(): void
    {
        // Given
        $id = ProductId::generate()->toString();

        // Then
        $this->expectException(ProductNotFoundException::class);

        // When
        $this->dispatch(new RepriceProduct($id, 1_950));
    }
}
