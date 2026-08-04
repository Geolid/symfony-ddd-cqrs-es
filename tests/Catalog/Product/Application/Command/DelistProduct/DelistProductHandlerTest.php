<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\Command\DelistProduct;

use Catalog\Product\Application\Command\DelistProduct\DelistProduct;
use Catalog\Product\Application\Command\ListProductForSale\ListProductForSale;
use Catalog\Product\Application\Finder\Product\ProductFinderInterface;
use Catalog\Product\Domain\Exception\ProductAlreadyDelistedException;
use Catalog\Product\Domain\Exception\ProductNotFoundException;
use Catalog\Product\Domain\ValueObject\ProductId;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class DelistProductHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itDelistsAProduct(): void
    {
        // Given
        $id = ProductId::generate()->toString();
        $this->dispatch(new ListProductForSale($id, 'Espresso cups, set of 6', 1_750));

        // When
        $this->dispatch(new DelistProduct($id));

        // Then
        $results = array_values(iterator_to_array($this->service(ProductFinderInterface::class)));
        self::assertCount(1, $results);
        self::assertTrue($results[0]->delisted);
    }

    #[Test]
    public function itFailsWhenTheProductDoesNotExist(): void
    {
        // Then
        $this->expectException(ProductNotFoundException::class);

        // When
        $this->dispatch(new DelistProduct(ProductId::generate()->toString()));
    }

    #[Test]
    public function itFailsWhenTheProductIsAlreadyDelisted(): void
    {
        // Given
        $id = ProductId::generate()->toString();
        $this->dispatch(new ListProductForSale($id, 'Espresso cups, set of 6', 1_750));
        $this->dispatch(new DelistProduct($id));

        // Then
        $this->expectException(ProductAlreadyDelistedException::class);

        // When
        $this->dispatch(new DelistProduct($id));
    }
}
