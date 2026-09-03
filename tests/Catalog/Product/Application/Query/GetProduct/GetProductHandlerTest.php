<?php

declare(strict_types=1);

namespace Catalog\Tests\Product\Application\Query\GetProduct;

use Catalog\Product\Application\Exception\ProductResultNotFoundException;
use Catalog\Product\Application\Query\GetProduct\GetProduct;
use Catalog\Tests\Product\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class GetProductHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itGets(): void
    {
        // Given
        $builder = ProductBuilder::new();
        $product = $builder->create();
        $this->store($product);

        // When
        $result = $this->ask(new GetProduct($product->id->toString()));

        // Then
        self::assertSame($product->id->toString(), $result->id);
        self::assertSame($builder['label']->value, $result->label);
        self::assertSame($builder['unitAmount']->cents, $result->unitAmountInCents);
        self::assertSame(
            $builder['listedAt']->format(\DateTimeImmutable::ATOM),
            $result->listedAt->format(\DateTimeImmutable::ATOM),
        );
        self::assertNull($result->repricedAt);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(ProductResultNotFoundException::class);

        // When
        $this->ask(new GetProduct(ProductBuilder::sample('id')->toString()));
    }
}
