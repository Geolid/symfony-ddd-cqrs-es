<?php

declare(strict_types=1);

namespace Shopping\Tests\Cart\Application\Command\AddCartProduct;

use Catalog\Tests\Listing\Support\Factory\ProductFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shopping\Cart\Application\Command\AddCartProduct\AddCartProduct;
use Shopping\Cart\Application\Command\AddCartProduct\Exception\ProductNotListedException;
use Shopping\Cart\Application\Finder\CartItem\CartItemFinderInterface;
use Shopping\Cart\Domain\Exception\CartNotFoundException;
use Shopping\Tests\Cart\Support\Factory\CartFactory;
use Shopping\Tests\Cart\Support\Factory\CartIdFactory;
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
        $cart = CartFactory::new()->create();
        $product = ProductFactory::new()->create();
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
        // Given
        $id = CartIdFactory::new()->create()->toString();

        // Then
        $this->expectException(CartNotFoundException::class);

        // When
        $this->dispatch(new AddCartProduct($id, Uuid::uuid7()->toString(), 1));
    }

    #[Test]
    public function itFailsWhenProductNotListed(): void
    {
        // Given
        $cart = CartFactory::new()->create();
        $this->store($cart);

        // Then
        $this->expectException(ProductNotListedException::class);

        // When
        $this->dispatch(new AddCartProduct($cart->id->toString(), Uuid::uuid7()->toString(), 1));
    }
}
