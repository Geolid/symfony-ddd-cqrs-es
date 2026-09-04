<?php

declare(strict_types=1);

namespace Catalog\Tests\Listing\Application\Query\GetProduct;

use Catalog\Listing\Application\Exception\ProductResultNotFoundException;
use Catalog\Listing\Application\Query\GetProduct\GetProduct;
use Catalog\Listing\Domain\ValueObject\ProductId;
use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
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
        self::assertSame($builder['unitAmount']->cents, $result->unitPriceInCents);
        self::assertSame(
            $builder['listedAt']->format(\DateTimeInterface::ATOM),
            $result->listedAt->format(\DateTimeInterface::ATOM),
        );
        self::assertNull($result->repricedAt);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(ProductResultNotFoundException::class);

        // When
        $this->ask(new GetProduct(ProductId::generate()->toString()));
    }
}
