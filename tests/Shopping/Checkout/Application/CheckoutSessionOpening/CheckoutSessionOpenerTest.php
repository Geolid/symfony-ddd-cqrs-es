<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\CheckoutSessionOpening;

use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Money;
use Shopping\Checkout\Application\CheckoutSessionOpening\CheckoutSessionOpener;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\CartPricesStaleException;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\ShopperAddressesNotCompletedException;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\ShopperErasureRequestedException;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\ShopperNotRegisteredException;
use Shopping\Checkout\Application\Finder\Cart\CartFinderInterface;
use Shopping\Checkout\Application\Finder\Cart\Exception\CartResultNotFoundException;
use Shopping\Checkout\Application\Finder\ListedProduct\ListedProductFinderInterface;
use Shopping\Checkout\Application\Finder\Shopper\ShopperFinderInterface;
use Shopping\Checkout\Domain\Cart\ValueObject\Product;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionOpened;
use Shopping\Tests\Checkout\Support\Builder\CartBuilder;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class CheckoutSessionOpenerTest extends AbstractIntegrationTestCase
{
    private CheckoutSessionOpener $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CheckoutSessionOpener(
            $this->service(CartFinderInterface::class),
            $this->service(ShopperFinderInterface::class),
            $this->service(ListedProductFinderInterface::class),
            $this->service(CommandBusInterface::class),
            $this->service(ClockInterface::class),
        );
    }

    #[Test]
    public function itOpens(): void
    {
        // Given
        $productBuilder = ProductBuilder::new();
        $catalogProduct = $productBuilder->create();
        $shopper = ShopperBuilder::new()->shippingAddressDefined()->billingAddressDefined()->create();
        $cartBuilder = CartBuilder::new()->withShopperId($shopper->id->toString())->lineAdded(
            product: Product::of($catalogProduct->id->toString(), $productBuilder['label'], $productBuilder['unitPrice']),
            quantity: CartBuilder::sample('quantity'),
        );
        $cart = $cartBuilder->create();
        $this->store($cart, $catalogProduct, $shopper);

        // When
        $result = $this->service->openFor($cart->id->toString());

        // Then
        self::assertSame($cartBuilder['quantity']->value * $productBuilder['unitPrice']->cents, $result->totalAmountInCents);
        self::assertNotNull($shopper->shippingAddress);
        self::assertSame(PostalAddressMapper::toArray($shopper->shippingAddress), PostalAddressMapper::toArray($result->shippingAddress));
        self::assertNotNull($shopper->billingAddress);
        self::assertSame(PostalAddressMapper::toArray($shopper->billingAddress), PostalAddressMapper::toArray($result->billingAddress));
        self::assertSame(
            Clock::get()->now()->modify('+30 minutes')->format(\DateTimeInterface::ATOM),
            $result->expiresAt->format(\DateTimeInterface::ATOM),
        );
        $event = $this->publishedEventOf(CheckoutSessionOpened::class);
        self::assertSame($result->checkoutSessionId, $event->id);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(CartResultNotFoundException::class);

        // When
        $this->service->openFor(Uuid::uuid7()->toString());
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
        $this->service->openFor($cart->id->toString());
    }

    #[Test]
    public function itFailsWhenShopperErasureRequested(): void
    {
        // Given
        $shopper = ShopperBuilder::new()->shippingAddressDefined()->billingAddressDefined()->erasureRequested()->create();
        $cart = CartBuilder::new()->withShopperId($shopper->id->toString())->lineAdded()->create();
        $this->store($cart, $shopper);

        // Then
        $this->expectException(ShopperErasureRequestedException::class);

        // When
        $this->service->openFor($cart->id->toString());
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
        $this->service->openFor($cart->id->toString());
    }

    #[Test]
    public function itFailsWhenOnlyShippingAddressCompleted(): void
    {
        // Given
        $shopper = ShopperBuilder::new()->shippingAddressDefined()->create();
        $cart = CartBuilder::new()->withShopperId($shopper->id->toString())->lineAdded()->create();
        $this->store($cart, $shopper);

        // Then
        $this->expectException(ShopperAddressesNotCompletedException::class);

        // When
        $this->service->openFor($cart->id->toString());
    }

    #[Test]
    public function itFailsWhenOnlyBillingAddressCompleted(): void
    {
        // Given
        $shopper = ShopperBuilder::new()->billingAddressDefined()->create();
        $cart = CartBuilder::new()->withShopperId($shopper->id->toString())->lineAdded()->create();
        $this->store($cart, $shopper);

        // Then
        $this->expectException(ShopperAddressesNotCompletedException::class);

        // When
        $this->service->openFor($cart->id->toString());
    }

    #[Test]
    public function itFailsWhenCartPricesStale(): void
    {
        // Given
        $productBuilder = ProductBuilder::new();
        $catalogProduct = $productBuilder->create();
        $shopper = ShopperBuilder::new()->shippingAddressDefined()->billingAddressDefined()->create();
        $staleProduct = Product::of($catalogProduct->id->toString(), $productBuilder['label'], Money::fromCents($productBuilder['unitPrice']->cents + 100));
        $cart = CartBuilder::new()->withShopperId($shopper->id->toString())->lineAdded(product: $staleProduct)->create();
        $this->store($cart, $catalogProduct, $shopper);

        // Then
        $this->expectException(CartPricesStaleException::class);

        // When
        $this->service->openFor($cart->id->toString());
    }
}
