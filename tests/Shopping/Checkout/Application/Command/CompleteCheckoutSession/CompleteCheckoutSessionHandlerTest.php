<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Application\Command\CompleteCheckoutSession;

use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Currency;
use Shared\Tests\Support\Factory\PostalAddressFactory;
use Shopping\Checkout\Application\CheckoutSessionStatus;
use Shopping\Checkout\Application\Command\CompleteCheckoutSession\CompleteCheckoutSession;
use Shopping\Checkout\Application\Finder\CheckoutSession\CheckoutSessionFinderInterface;
use Shopping\Checkout\Application\Mapper\CheckoutItemMapper;
use Shopping\Checkout\Domain\Exception\CheckoutSessionNotFoundException;
use Shopping\Tests\Checkout\Support\Factory\CheckoutItemFactory;
use Shopping\Tests\Checkout\Support\Factory\CheckoutSessionFactory;
use Shopping\Tests\Checkout\Support\Factory\CheckoutSessionIdFactory;
use Shopping\Tests\Checkout\Support\Factory\TaxRateFactory;
use Support\TestCase\AbstractIntegrationTestCase;

final class CompleteCheckoutSessionHandlerTest extends AbstractIntegrationTestCase
{
    private CheckoutSessionFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(CheckoutSessionFinderInterface::class);
    }

    #[Test]
    public function itCompletes(): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()->create();
        $this->store($checkoutSession);

        // When
        $this->dispatch(new CompleteCheckoutSession(
            id: $checkoutSession->id->toString(),
            cartId: $checkoutSession->cartId,
            customerId: $checkoutSession->customerId,
            items: array_map(CheckoutItemMapper::toArray(...), $checkoutSession->items),
            currency: $checkoutSession->total->excludingTax->currency->value,
            taxRateBasisPoints: $checkoutSession->items[0]->taxRate->basisPoints,
            shippingAddress: PostalAddressMapper::toArray($checkoutSession->shippingAddress),
            billingAddress: PostalAddressMapper::toArray($checkoutSession->billingAddress),
            paymentId: Uuid::uuid7()->toString(),
        ));

        // Then
        $result = $this->finder->ofId($checkoutSession->id->toString());
        self::assertSame(CheckoutSessionStatus::COMPLETED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenAlreadyCompleted(): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()->completed()->create();
        $cartId = Uuid::uuid7()->toString();
        $customerId = Uuid::uuid7()->toString();
        $items = array_map(CheckoutItemMapper::toArray(...), CheckoutItemFactory::new()->many(2)->create());
        $taxRateBasisPoints = TaxRateFactory::new()->create()->basisPoints;
        $shippingAddress = PostalAddressMapper::toArray(PostalAddressFactory::new()->create());
        $billingAddress = PostalAddressMapper::toArray(PostalAddressFactory::new()->create());
        $paymentId = Uuid::uuid7()->toString();

        $this->store($checkoutSession);

        // When
        $this->dispatch(new CompleteCheckoutSession(
            id: $checkoutSession->id->toString(),
            cartId: $cartId,
            customerId: $customerId,
            items: $items,
            currency: Currency::EUR->value,
            taxRateBasisPoints: $taxRateBasisPoints,
            shippingAddress: $shippingAddress,
            billingAddress: $billingAddress,
            paymentId: $paymentId,
        ));

        // Then
        self::expectNotToPerformAssertions();
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $unknownId = CheckoutSessionIdFactory::new()->create()->toString();
        $cartId = Uuid::uuid7()->toString();
        $customerId = Uuid::uuid7()->toString();
        $items = array_map(CheckoutItemMapper::toArray(...), CheckoutItemFactory::new()->many(2)->create());
        $taxRateBasisPoints = TaxRateFactory::new()->create()->basisPoints;
        $shippingAddress = PostalAddressMapper::toArray(PostalAddressFactory::new()->create());
        $billingAddress = PostalAddressMapper::toArray(PostalAddressFactory::new()->create());
        $paymentId = Uuid::uuid7()->toString();

        // Then
        $this->expectException(CheckoutSessionNotFoundException::class);

        // When
        $this->dispatch(new CompleteCheckoutSession(
            id: $unknownId,
            cartId: $cartId,
            customerId: $customerId,
            items: $items,
            currency: Currency::EUR->value,
            taxRateBasisPoints: $taxRateBasisPoints,
            shippingAddress: $shippingAddress,
            billingAddress: $billingAddress,
            paymentId: $paymentId,
        ));
    }
}
