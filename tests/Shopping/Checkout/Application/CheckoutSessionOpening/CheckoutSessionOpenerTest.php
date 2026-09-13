<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\CheckoutSessionOpening;

use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
use Crm\Tests\Customer\Support\Builder\CustomerBuilder;
use PHPUnit\Framework\Attributes\Test;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Mapper\PostalAddressMapper;
use Shopping\Checkout\Application\CheckoutSessionOpening\CheckoutSessionOpener;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\CustomerAddressesNotCompletedException;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\CustomerErasureRequestedException;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\CustomerNotRegisteredException;
use Shopping\Checkout\Application\Finder\Cart\CartFinderInterface;
use Shopping\Checkout\Application\Finder\Cart\Exception\CartResultNotFoundException;
use Shopping\Checkout\Application\Finder\CartItem\CartItemFinderInterface;
use Shopping\Checkout\Application\Finder\Customer\CustomerFinderInterface;
use Shopping\Checkout\Application\Finder\ListedProduct\ListedProductFinderInterface;
use Shopping\Checkout\Domain\Cart\ValueObject\Quantity;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionOpened;
use Shopping\Tests\Checkout\Support\Builder\CartBuilder;
use Support\SeededFaker;
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
            $this->service(CartItemFinderInterface::class),
            $this->service(CustomerFinderInterface::class),
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
        $secondProductBuilder = ProductBuilder::new();
        $secondCatalogProduct = $secondProductBuilder->create();
        $customer = CustomerBuilder::new()->shippingAddressDefined()->billingAddressDefined()->create();
        $cartBuilder = CartBuilder::new()->withCustomerId($customer->id->toString())->productAdded(
            productId: $catalogProduct->id->toString(),
            quantity: $quantity = Quantity::of(SeededFaker::get()->numberBetween(1, 5)),
        )->productAdded(
            productId: $secondCatalogProduct->id->toString(),
            quantity: $secondQuantity = Quantity::of(SeededFaker::get()->numberBetween(1, 5)),
        );
        $cart = $cartBuilder->create();
        $this->store($cart, $catalogProduct, $secondCatalogProduct, $customer);

        // When
        $result = $this->service->openFor($cart->id->toString());

        // Then
        self::assertSame(
            $quantity->value * $productBuilder['unitPrice']->cents + $secondQuantity->value * $secondProductBuilder['unitPrice']->cents,
            $result->totalAmountInCents,
        );
        self::assertNotNull($customer->shippingAddress);
        self::assertSame(PostalAddressMapper::toArray($customer->shippingAddress), PostalAddressMapper::toArray($result->shippingAddress));
        self::assertNotNull($customer->billingAddress);
        self::assertSame(PostalAddressMapper::toArray($customer->billingAddress), PostalAddressMapper::toArray($result->billingAddress));
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
    public function itFailsWhenCustomerNotRegistered(): void
    {
        // Given
        $cart = CartBuilder::new()->productAdded()->create();
        $this->store($cart);

        // Then
        $this->expectException(CustomerNotRegisteredException::class);

        // When
        $this->service->openFor($cart->id->toString());
    }

    #[Test]
    public function itFailsWhenCustomerErasureRequested(): void
    {
        // Given
        $customer = CustomerBuilder::new()->shippingAddressDefined()->billingAddressDefined()->erasureRequested()->create();
        $cart = CartBuilder::new()->withCustomerId($customer->id->toString())->productAdded()->create();
        $this->store($cart, $customer);

        // Then
        $this->expectException(CustomerErasureRequestedException::class);

        // When
        $this->service->openFor($cart->id->toString());
    }

    #[Test]
    public function itFailsWhenCustomerAddressesNotCompleted(): void
    {
        // Given
        $customer = CustomerBuilder::new()->create();
        $cart = CartBuilder::new()->withCustomerId($customer->id->toString())->productAdded()->create();
        $this->store($cart, $customer);

        // Then
        $this->expectException(CustomerAddressesNotCompletedException::class);

        // When
        $this->service->openFor($cart->id->toString());
    }

    #[Test]
    public function itFailsWhenOnlyShippingAddressCompleted(): void
    {
        // Given
        $customer = CustomerBuilder::new()->shippingAddressDefined()->create();
        $cart = CartBuilder::new()->withCustomerId($customer->id->toString())->productAdded()->create();
        $this->store($cart, $customer);

        // Then
        $this->expectException(CustomerAddressesNotCompletedException::class);

        // When
        $this->service->openFor($cart->id->toString());
    }

    #[Test]
    public function itFailsWhenOnlyBillingAddressCompleted(): void
    {
        // Given
        $customer = CustomerBuilder::new()->billingAddressDefined()->create();
        $cart = CartBuilder::new()->withCustomerId($customer->id->toString())->productAdded()->create();
        $this->store($cart, $customer);

        // Then
        $this->expectException(CustomerAddressesNotCompletedException::class);

        // When
        $this->service->openFor($cart->id->toString());
    }
}
