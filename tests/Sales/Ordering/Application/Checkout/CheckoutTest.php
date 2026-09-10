<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Checkout;

use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Checkout\Checkout;
use Sales\Ordering\Application\Checkout\Exception\CartOutdatedException;
use Sales\Ordering\Application\Checkout\Exception\ShopperAddressesNotCompletedException;
use Sales\Ordering\Application\Checkout\Exception\ShopperErasureRequestedException;
use Sales\Ordering\Application\Checkout\Exception\ShopperNotRegisteredException;
use Sales\Ordering\Application\Finder\ListedProduct\ListedProductFinderInterface;
use Sales\Ordering\Application\Finder\Shopper\ShopperFinderInterface;
use Sales\Ordering\Domain\Cart\Event\CartCheckedOut;
use Sales\Ordering\Domain\Cart\Exception\CartNotFoundException;
use Sales\Ordering\Domain\Cart\Repository\CartRepositoryInterface;
use Sales\Ordering\Domain\Shared\ValueObject\Product;
use Sales\Tests\Ordering\Support\Builder\CartBuilder;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Money;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class CheckoutTest extends AbstractIntegrationTestCase
{
    private Checkout $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new Checkout(
            $this->service(CartRepositoryInterface::class),
            $this->service(ShopperFinderInterface::class),
            $this->service(ListedProductFinderInterface::class),
            $this->service(ClockInterface::class),
        );
    }

    #[Test]
    public function itChecksOut(): void
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
        $result = $this->service->checkout($cart->id->toString());

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
        $this->service->checkout(Uuid::uuid7()->toString());
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
        $this->service->checkout($cart->id->toString());
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
        $this->service->checkout($cart->id->toString());
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
        $this->service->checkout($cart->id->toString());
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
        $this->service->checkout($cart->id->toString());
    }
}
