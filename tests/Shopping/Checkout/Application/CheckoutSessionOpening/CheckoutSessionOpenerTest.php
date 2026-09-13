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
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\PostalAddress;
use Shared\Domain\ValueObject\Quantity;
use Shared\Domain\ValueObject\TaxedAmount;
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
use Shopping\Tests\Cart\Support\Builder\CartBuilder;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

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
        );
    }

    #[Test]
    public function itOpens(): void
    {
        // Given
        $productBuilder = ProductBuilder::new()->withUnitPriceInCents(5_002);
        $catalogProduct = $productBuilder->create();
        $secondProductBuilder = ProductBuilder::new()->withUnitPriceInCents(5_007);
        $secondCatalogProduct = $secondProductBuilder->create();
        $customer = CustomerBuilder::new()
            ->shippingAddressDefined(PostalAddress::of('Jane Doe', Address::of('10 Rue de la Paix', '75002', 'Paris', 'FR')))
            ->billingAddressDefined()
            ->create();
        $cartBuilder = CartBuilder::new()->withCustomerId($customer->id->toString())->productAdded(
            productId: $catalogProduct->id->toString(),
            quantity: $quantity = Quantity::of(1),
        )->productAdded(
            productId: $secondCatalogProduct->id->toString(),
            quantity: $secondQuantity = Quantity::of(1),
        );
        $cart = $cartBuilder->create();
        $this->store($cart, $catalogProduct, $secondCatalogProduct, $customer);

        // When
        $result = $this->service->openFor($cart->id->toString());

        // Then
        self::assertNotNull($customer->shippingAddress);
        $taxRate = $this->taxRateResolver->resolve($customer->shippingAddress->address->countryCode);
        $firstExcludingTax = $productBuilder['unitPrice']->times($quantity->value);
        $secondExcludingTax = $secondProductBuilder['unitPrice']->times($secondQuantity->value);
        $expectedTotal = TaxedAmount::of($firstExcludingTax, $this->taxAmountOf($firstExcludingTax, $taxRate->basisPoints))
            ->plus(TaxedAmount::of($secondExcludingTax, $this->taxAmountOf($secondExcludingTax, $taxRate->basisPoints)));
        self::assertSame($expectedTotal->excludingTax->cents, $result->total->excludingTax->cents);
        self::assertSame($expectedTotal->taxAmount->cents, $result->total->taxAmount->cents);
        self::assertSame($expectedTotal->includingTax->cents, $result->total->includingTax->cents);
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

    #[Test]
    public function itFailsWhenProductNotListed(): void
    {
        // Given
        $customer = CustomerBuilder::new()->shippingAddressDefined()->billingAddressDefined()->create();
        $cart = CartBuilder::new()->withCustomerId($customer->id->toString())->productAdded()->create();
        $this->store($cart, $customer);

        // Then
        $this->expectException(ProductNotListedException::class);

        // When
        $this->service->openFor($cart->id->toString());
    }

    private function taxAmountOf(Money $excludingTax, int $basisPoints): Money
    {
        return Money::fromCents((int) round($excludingTax->cents * $basisPoints / 10_000), $excludingTax->currency->value);
    }
}
