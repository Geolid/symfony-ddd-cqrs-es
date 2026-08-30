<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\Query\GetProduct;

use Catalog\Product\Application\Exception\ProductResultNotFoundException;
use Catalog\Product\Application\Query\GetProduct\GetProduct;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class GetProductHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = ProductTestFactory::new()->create();
        $product = ProductTestFactory::new()->withLabel('Espresso cups, set of 6')->withUnitAmountInCents(1_750)->create();
        $this->store($other, $product);

        // When
        $result = $this->ask(new GetProduct($product->id->toString()));

        // Then
        self::assertSame($product->id->toString(), $result->id);
        self::assertSame('Espresso cups, set of 6', $result->label);
        self::assertSame(1_750, $result->unitAmountInCents);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = ProductTestFactory::new()->attribute('id')->toString();

        // Then
        $this->expectException(ProductResultNotFoundException::class);

        // When
        $this->ask(new GetProduct($id));
    }
}
