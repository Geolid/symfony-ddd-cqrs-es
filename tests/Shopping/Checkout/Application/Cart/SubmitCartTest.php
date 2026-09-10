<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Cart;

use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid\Uuid;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Money;
use Shopping\Checkout\Application\Cart\Exception\CartOutdatedException;
use Shopping\Checkout\Application\Cart\Exception\ShopperAddressesNotCompletedException;
use Shopping\Checkout\Application\Cart\Exception\ShopperErasureRequestedException;
use Shopping\Checkout\Application\Cart\Exception\ShopperNotRegisteredException;
use Shopping\Checkout\Application\Cart\SubmitCart;
use Shopping\Checkout\Application\Finder\ListedProduct\ListedProductFinderInterface;
use Shopping\Checkout\Application\Finder\Shopper\ShopperFinderInterface;
use Shopping\Checkout\Domain\Cart\Event\CartCheckedOut;
use Shopping\Checkout\Domain\Cart\Exception\CartNotFoundException;
use Shopping\Checkout\Domain\Cart\Repository\CartRepositoryInterface;
use Shopping\Checkout\Domain\Cart\ValueObject\Product;
use Shopping\Tests\Checkout\Support\Builder\CartBuilder;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class SubmitCartTest extends AbstractIntegrationTestCase
{
    private SubmitCart $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SubmitCart(
            $this->service(CartRepositoryInterface::class),
            $this->service(ShopperFinderInterface::class),
            $this->service(ListedProductFinderInterface::class),
            $this->service(ClockInterface::class),
        );
    }

    #[Test]
    public function itSubmits(): void
    {
        // Given
        $productBuilder = ProductBuilder::new();
        $catalogProduct = $productBuilder->create();
        $shopper = ShopperBuilder::new()->billingAddressDefined()->create();
        $cartBuilder = CartBuilder::new()->withShopperId($shopper->id->toString())->lineAdded(
            product: Product::of($catalogProduct->id->toString(), $productBuilder['label'], $productBuilder['unitPrice']),
            quantity: CartBuilder::sample('quantity'),
        );
        $cart = $cartBuilder->create();
        $this->store($cart, $catalogProduct, $shopper);

        // When
        $result = $this->service->submit($cart->id->toString());

        // Then
        self::assertSame($cart->id->toString(), $result->cartId);
        self::assertSame($cartBuilder['quantity']->value * $productBuilder['unitPrice']->cents, $result->totalAmountInCents);
        self::assertNotNull($shopper->billingAddress);
        self::assertSame(PostalAddressMapper::toArray($shopper->billingAddress), PostalAddressMapper::toArray($result->billingAddress));
        $event = $this->publishedEventOf(CartCheckedOut::class);
        self::assertSame($cart->id->toString(), $event->id);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(CartNotFoundException::class);

        // When
        $this->service->submit(Uuid::uuid7()->toString());
    }

    #[Test]
    public function itFailsWhenShopperNotRegistered(): void
    {
        // Given
        $cart = CartBuilder::new()->lineAdded()->create();
        $this->store($cart);

        // Then
        $this->expectException(ShopperNotRegisteredException::class);

        // When
        $this->service->submit($cart->id->toString());
    }

    #[Test]
    public function itFailsWhenShopperErasureRequested(): void
    {
        // Given
        $shopper = ShopperBuilder::new()->billingAddressDefined()->erasureRequested()->create();
        $cart = CartBuilder::new()->withShopperId($shopper->id->toString())->lineAdded()->create();
        $this->store($cart, $shopper);

        // Then
        $this->expectException(ShopperErasureRequestedException::class);

        // When
        $this->service->submit($cart->id->toString());
    }

    #[Test]
    public function itFailsWhenShopperAddressesNotCompleted(): void
    {
        // Given
        $shopper = ShopperBuilder::new()->create();
        $cart = CartBuilder::new()->withShopperId($shopper->id->toString())->lineAdded()->create();
        $this->store($cart, $shopper);

        // Then
        $this->expectException(ShopperAddressesNotCompletedException::class);

        // When
        $this->service->submit($cart->id->toString());
    }

    #[Test]
    public function itFailsWhenCartOutdated(): void
    {
        // Given
        $productBuilder = ProductBuilder::new();
        $catalogProduct = $productBuilder->create();
        $shopper = ShopperBuilder::new()->billingAddressDefined()->create();
        $staleProduct = Product::of($catalogProduct->id->toString(), $productBuilder['label'], Money::fromCents($productBuilder['unitPrice']->cents + 100));
        $cart = CartBuilder::new()->withShopperId($shopper->id->toString())->lineAdded(product: $staleProduct)->create();
        $this->store($cart, $catalogProduct, $shopper);

        // Then
        $this->expectException(CartOutdatedException::class);

        // When
        $this->service->submit($cart->id->toString());
    }
}
