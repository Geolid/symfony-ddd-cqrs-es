<?php

declare(strict_types=1);

namespace Sales\Tests\Ordering\Application\Checkout;

use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
use PHPUnit\Framework\Attributes\Test;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid\Uuid;
use Sales\Ordering\Application\Checkout\Checkout;
use Sales\Ordering\Application\Checkout\Exception\BuyerAddressesNotCompletedException;
use Sales\Ordering\Application\Checkout\Exception\BuyerErasureRequestedException;
use Sales\Ordering\Application\Checkout\Exception\BuyerNotRegisteredException;
use Sales\Ordering\Application\Checkout\Exception\CartOutdatedException;
use Sales\Ordering\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Ordering\Application\Finder\ListedProduct\ListedProductFinderInterface;
use Sales\Ordering\Domain\Cart\Event\CartCheckedOut;
use Sales\Ordering\Domain\Cart\Exception\CartNotFoundException;
use Sales\Ordering\Domain\Cart\Repository\CartRepositoryInterface;
use Sales\Ordering\Domain\Shared\ValueObject\Product;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Sales\Tests\Ordering\Support\Builder\CartBuilder;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Money;
use Support\TestCase\AbstractIntegrationTestCase;

final class CheckoutTest extends AbstractIntegrationTestCase
{
    private Checkout $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new Checkout(
            $this->service(CartRepositoryInterface::class),
            $this->service(BuyerFinderInterface::class),
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
        $buyerBuilder = BuyerBuilder::new()->billingAddressDefined();
        $buyer = $buyerBuilder->create();
        $cartBuilder = CartBuilder::new()->withBuyerId($buyer->id->toString())->lineAdded(
            product: Product::of($catalogProduct->id->toString(), $productBuilder['label'], $productBuilder['unitPrice']),
            quantity: CartBuilder::sample('quantity'),
        );
        $cart = $cartBuilder->create();
        $this->store($cart, $catalogProduct, $buyer);

        // When
        $result = $this->service->checkout($cart->id->toString());

        // Then
        self::assertSame($cart->id->toString(), $result->cartId);
        self::assertSame($cartBuilder['quantity']->value * $productBuilder['unitPrice']->cents, $result->totalAmountInCents);
        self::assertSame(PostalAddressMapper::toArray($buyerBuilder['billingAddress']), PostalAddressMapper::toArray($result->billingAddress));
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
    public function itFailsWhenBuyerNotRegistered(): void
    {
        // Given
        $cart = CartBuilder::new()->lineAdded()->create();
        $this->store($cart);

        // Then
        $this->expectException(BuyerNotRegisteredException::class);

        // When
        $this->service->checkout($cart->id->toString());
    }

    #[Test]
    public function itFailsWhenBuyerErasureRequested(): void
    {
        // Given
        $buyer = BuyerBuilder::new()->billingAddressDefined()->erasureRequested()->create();
        $cart = CartBuilder::new()->withBuyerId($buyer->id->toString())->lineAdded()->create();
        $this->store($cart, $buyer);

        // Then
        $this->expectException(BuyerErasureRequestedException::class);

        // When
        $this->service->checkout($cart->id->toString());
    }

    #[Test]
    public function itFailsWhenBuyerAddressesNotCompleted(): void
    {
        // Given
        $buyer = BuyerBuilder::new()->create();
        $cart = CartBuilder::new()->withBuyerId($buyer->id->toString())->lineAdded()->create();
        $this->store($cart, $buyer);

        // Then
        $this->expectException(BuyerAddressesNotCompletedException::class);

        // When
        $this->service->checkout($cart->id->toString());
    }

    #[Test]
    public function itFailsWhenCartOutdated(): void
    {
        // Given
        $productBuilder = ProductBuilder::new();
        $catalogProduct = $productBuilder->create();
        $buyer = BuyerBuilder::new()->billingAddressDefined()->create();
        $staleProduct = Product::of($catalogProduct->id->toString(), $productBuilder['label'], Money::fromCents($productBuilder['unitPrice']->cents + 100));
        $cart = CartBuilder::new()->withBuyerId($buyer->id->toString())->lineAdded(product: $staleProduct)->create();
        $this->store($cart, $catalogProduct, $buyer);

        // Then
        $this->expectException(CartOutdatedException::class);

        // When
        $this->service->checkout($cart->id->toString());
    }
}
