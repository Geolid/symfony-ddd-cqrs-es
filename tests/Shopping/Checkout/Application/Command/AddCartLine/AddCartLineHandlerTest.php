<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Command\AddCartLine;

use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shopping\Checkout\Application\Command\AddCartLine\AddCartLine;
use Shopping\Checkout\Application\Command\AddCartLine\Exception\ProductNotListedException;
use Shopping\Checkout\Domain\Cart\Exception\CartNotFoundException;
use Shopping\Checkout\Domain\Cart\Repository\CartRepositoryInterface;
use Shopping\Tests\Checkout\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class AddCartLineHandlerTest extends AbstractIntegrationTestCase
{
    private CartRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(CartRepositoryInterface::class);
    }

    #[Test]
    public function itAdds(): void
    {
        // Given
        $cart = CartBuilder::new()->create();
        $product = ProductBuilder::new()->create();
        $this->store($cart, $product);

        // When
        $this->dispatch(new AddCartLine($cart->id->toString(), $product->id->toString(), 2));

        // Then
        $reloaded = $this->repository->load($cart->id);
        $lines = $reloaded->lines();
        self::assertCount(1, $lines);
        self::assertSame($product->id->toString(), $lines[0]->product->id);
        self::assertSame(2, $lines[0]->quantity->value);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(CartNotFoundException::class);

        // When
        $this->dispatch(new AddCartLine(Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), 1));
    }

    #[Test]
    public function itFailsWhenProductNotListed(): void
    {
        // Given
        $cart = CartBuilder::new()->create();
        $this->store($cart);

        // Then
        $this->expectException(ProductNotListedException::class);

        // When
        $this->dispatch(new AddCartLine($cart->id->toString(), Uuid::uuid7()->toString(), 1));
    }
}
