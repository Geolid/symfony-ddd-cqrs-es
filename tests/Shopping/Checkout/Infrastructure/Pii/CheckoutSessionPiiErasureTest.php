<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\Pii;

use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\Pii\ErasedPostalAddress;
use Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionCompleted\CheckoutSessionCompletedIntegrationEvent;
use Shopping\Checkout\Domain\Event\CheckoutSessionCompleted;
use Shopping\Checkout\Domain\Event\CheckoutSessionOpened;
use Shopping\Tests\Checkout\Support\Factory\CheckoutSessionFactory;
use Support\TestCase\AbstractIntegrationTestCase;

final class CheckoutSessionPiiErasureTest extends AbstractIntegrationTestCase
{
    private CipherKeyStore $cipherKeyStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipherKeyStore = $this->service(CipherKeyStore::class);
    }

    #[Test]
    public function itCryptoShredsShippingAddressOnErasure(): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()->create();
        $this->store($checkoutSession);

        // When
        $this->cipherKeyStore->removeWithSubjectId($checkoutSession->id->toString());

        // Then
        $erased = $this->storedEventOf(CheckoutSessionOpened::class, $checkoutSession->id->toString());
        self::assertSame(PostalAddressMapper::toArray((new ErasedPostalAddress())()), PostalAddressMapper::toArray($erased->shippingAddress));
    }

    #[Test]
    public function itCryptoShredsBillingAddressOnErasure(): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()->create();
        $this->store($checkoutSession);

        // When
        $this->cipherKeyStore->removeWithSubjectId($checkoutSession->id->toString());

        // Then
        $erased = $this->storedEventOf(CheckoutSessionOpened::class, $checkoutSession->id->toString());
        self::assertSame(PostalAddressMapper::toArray((new ErasedPostalAddress())()), PostalAddressMapper::toArray($erased->billingAddress));
    }

    #[Test]
    public function itCryptoShredsShippingAddressOnCheckoutSessionCompletedErasure(): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()->completed()->create();
        $this->store($checkoutSession);

        // When
        $this->cipherKeyStore->removeWithSubjectId($checkoutSession->id->toString());

        // Then
        $erased = $this->storedEventOf(CheckoutSessionCompleted::class, $checkoutSession->id->toString());
        self::assertSame(PostalAddressMapper::toArray((new ErasedPostalAddress())()), PostalAddressMapper::toArray($erased->shippingAddress));
    }

    #[Test]
    public function itCryptoShredsBillingAddressOnCheckoutSessionCompletedErasure(): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()->completed()->create();
        $this->store($checkoutSession);

        // When
        $this->cipherKeyStore->removeWithSubjectId($checkoutSession->id->toString());

        // Then
        $erased = $this->storedEventOf(CheckoutSessionCompleted::class, $checkoutSession->id->toString());
        self::assertSame(PostalAddressMapper::toArray((new ErasedPostalAddress())()), PostalAddressMapper::toArray($erased->billingAddress));
    }

    #[Test]
    public function itCryptoShredsCheckoutSessionCompletedIntegrationEventShippingAddressOnErasure(): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()->completed()->create();
        $this->store($checkoutSession);

        // When
        $this->cipherKeyStore->removeWithSubjectId($checkoutSession->id->toString());

        // Then
        $erased = $this->storedEventOf(CheckoutSessionCompletedIntegrationEvent::class, $checkoutSession->id->toString());
        self::assertSame(PostalAddressMapper::toArray((new ErasedPostalAddress())()), $erased->shippingAddress);
    }

    #[Test]
    public function itCryptoShredsCheckoutSessionCompletedIntegrationEventBillingAddressOnErasure(): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()->completed()->create();
        $this->store($checkoutSession);

        // When
        $this->cipherKeyStore->removeWithSubjectId($checkoutSession->id->toString());

        // Then
        $erased = $this->storedEventOf(CheckoutSessionCompletedIntegrationEvent::class, $checkoutSession->id->toString());
        self::assertSame(PostalAddressMapper::toArray((new ErasedPostalAddress())()), $erased->billingAddress);
    }
}
