<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Application\Query\GetProduct;

use Catalog\Listing\Application\Finder\Product\Exception\ProductResultNotFoundException;
use Catalog\Listing\Application\Query\GetProduct\GetProduct;
use Catalog\Tests\Listing\Support\Factory\ProductFactory;
use Catalog\Tests\Listing\Support\Factory\ProductIdFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class GetProductHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGets(): void
    {
        // Given
        $product = ProductFactory::new()->create();
        $this->store($product);

        // When
        $result = $this->ask(new GetProduct($product->id->toString()));

        // Then
        self::assertSame($product->id->toString(), $result->id);
        self::assertSame($product->label->value, $result->label);
        self::assertSame($product->unitPrice->cents, $result->unitPriceInCents);
        self::assertSame(
            $product->listedAt->format(\DateTimeInterface::ATOM),
            $result->listedAt->format(\DateTimeInterface::ATOM),
        );
        self::assertNull($result->repricedAt);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = ProductIdFactory::new()->create()->toString();

        // Then
        $this->expectException(ProductResultNotFoundException::class);

        // When
        $this->ask(new GetProduct($id));
    }
}
