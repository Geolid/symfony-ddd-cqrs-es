<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Command\AddCartProduct;

use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shopping\Checkout\Application\Command\AddCartProduct\AddCartProduct;
use Shopping\Checkout\Application\Command\AddCartProduct\Exception\ProductNotListedException;
use Shopping\Checkout\Application\Finder\CartItem\CartItemFinderInterface;
use Shopping\Checkout\Domain\Cart\Exception\CartNotFoundException;
use Shopping\Tests\Checkout\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class AddCartProductHandlerTest extends AbstractIntegrationTestCase
{
    private CartItemFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(CartItemFinderInterface::class);
    }

    #[Test]
    public function itAdds(): void
    {
        // Given
        $cart = CartBuilder::new()->create();
        $product = ProductBuilder::new()->create();
        $this->store($cart, $product);

        // When
        $this->dispatch(new AddCartProduct($cart->id->toString(), $product->id->toString(), 2));

        // Then
        $items = iterator_to_array($this->finder->byCart($cart->id->toString()));
        self::assertCount(1, $items);
        self::assertSame($product->id->toString(), $items[0]->productId);
        self::assertSame(2, $items[0]->quantity);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(CartNotFoundException::class);

        // When
        $this->dispatch(new AddCartProduct(Uuid::uuid7()->toString(), Uuid::uuid7()->toString(), 1));
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
        $this->dispatch(new AddCartProduct($cart->id->toString(), Uuid::uuid7()->toString(), 1));
    }
}
