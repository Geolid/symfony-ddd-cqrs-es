<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\CheckoutSessionOpening;

use Catalog\Tests\Listing\Support\Factory\ProductFactory;
use Crm\Tests\Customer\Support\Factory\CustomerFactory;
use PHPUnit\Framework\Attributes\Test;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Quantity;
use Shared\Tests\Support\Factory\AddressFactory;
use Shared\Tests\Support\Factory\PostalAddressFactory;
use Shopping\Checkout\Application\CheckoutSessionOpening\CheckoutSessionOpener;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\CustomerAddressesNotCompletedException;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\CustomerErasureRequestedException;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\CustomerNotRegisteredException;
use Shopping\Checkout\Application\CheckoutSessionOpening\Exception\ProductNotListedException;
use Shopping\Checkout\Application\Finder\Cart\CartFinderInterface;
use Shopping\Checkout\Application\Finder\Cart\Exception\CartResultNotFoundException;
use Shopping\Checkout\Application\Finder\CartItem\CartItemFinderInterface;
use Shopping\Checkout\Application\Finder\Customer\CustomerFinderInterface;
use Shopping\Checkout\Application\Finder\ListedProduct\ListedProductFinderInterface;
use Shopping\Checkout\Application\Tax\TaxRateResolverInterface;
use Shopping\Checkout\Domain\Event\CheckoutSessionOpened;
use Shopping\Checkout\Domain\Service\CheckoutSessionExpiryCalculator;
use Shopping\Tests\Cart\Support\Factory\CartFactory;
use Support\TestCase\AbstractIntegrationTestCase;

final class CheckoutSessionOpenerTest extends AbstractIntegrationTestCase
{
    private CheckoutSessionOpener $service;
    private TaxRateResolverInterface $taxRateResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taxRateResolver = $this->service(TaxRateResolverInterface::class);
        $this->service = new CheckoutSessionOpener(
            $this->service(CartFinderInterface::class),
            $this->service(CartItemFinderInterface::class),
            $this->service(CustomerFinderInterface::class),
            $this->service(ListedProductFinderInterface::class),
            $this->taxRateResolver,
            $this->service(CommandBusInterface::class),
            $this->service(ClockInterface::class),
            $this->service(CheckoutSessionExpiryCalculator::class),
        );
    }

    #[Test]
    public function itOpens(): void
    {
        // Given
        $catalogProduct = ProductFactory::new()->withUnitPriceInCents(1_497)->create();
        $otherCatalogProduct = ProductFactory::new()->withUnitPriceInCents(2_497)->create();
        $shippingAddress = PostalAddressFactory::new()->withAddress(AddressFactory::new()->withCountryCode('FR')->create())->create();
        $customer = CustomerFactory::new()
            ->shippingAddressDefined($shippingAddress)
            ->billingAddressDefined()
            ->create();
        $cart = CartFactory::new()->withCustomerId($customer->id->toString())->productAdded(
            productId: $catalogProduct->id->toString(),
            quantity: Quantity::of(1),
        )->productAdded(
            productId: $otherCatalogProduct->id->toString(),
            quantity: Quantity::of(1),
        )->create();
        $this->store($cart, $catalogProduct, $otherCatalogProduct, $customer);

        // When
        $result = $this->service->openFor($cart->id->toString());

        // Then
        self::assertSame(3_994, $result->total->excludingTax->cents);
        self::assertSame(798, $result->total->taxAmount->cents);
        self::assertSame(4_792, $result->total->includingTax->cents);
        self::assertNotNull($customer->shippingAddress);
        self::assertSame(PostalAddressMapper::toArray($customer->shippingAddress), PostalAddressMapper::toArray($result->shippingAddress));
        self::assertNotNull($customer->billingAddress);
        self::assertSame(PostalAddressMapper::toArray($customer->billingAddress), PostalAddressMapper::toArray($result->billingAddress));
        $event = $this->publishedEventOf(CheckoutSessionOpened::class);
        self::assertSame($result->checkoutSessionId, $event->id);
        self::assertSameDateTime($event->expiresAt, $result->expiresAt);
        self::assertGreaterThan($event->openedAt, $event->expiresAt);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $unknownCartId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(CartResultNotFoundException::class);

        // When
        $this->service->openFor($unknownCartId);
    }

    #[Test]
    public function itFailsWhenCustomerNotRegistered(): void
    {
        // Given
        $cart = CartFactory::new()->productAdded()->create();
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
        $customer = CustomerFactory::new()->shippingAddressDefined()->billingAddressDefined()->erasureRequested()->create();
        $cart = CartFactory::new()->withCustomerId($customer->id->toString())->productAdded()->create();
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
        $customer = CustomerFactory::new()->create();
        $cart = CartFactory::new()->withCustomerId($customer->id->toString())->productAdded()->create();
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
        $customer = CustomerFactory::new()->shippingAddressDefined()->create();
        $cart = CartFactory::new()->withCustomerId($customer->id->toString())->productAdded()->create();
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
        $customer = CustomerFactory::new()->billingAddressDefined()->create();
        $cart = CartFactory::new()->withCustomerId($customer->id->toString())->productAdded()->create();
        $this->store($cart, $customer);

        // Then
        $this->expectException(CustomerAddressesNotCompletedException::class);

        // When
        $this->service->openFor($cart->id->toString());
    }

    #[Test]
    public function itFailsWhenProductNotListed(): void
    {
        // Given
        $customer = CustomerFactory::new()->shippingAddressDefined()->billingAddressDefined()->create();
        $cart = CartFactory::new()->withCustomerId($customer->id->toString())->productAdded()->create();
        $this->store($cart, $customer);

        // Then
        $this->expectException(ProductNotListedException::class);

        // When
        $this->service->openFor($cart->id->toString());
    }
}
