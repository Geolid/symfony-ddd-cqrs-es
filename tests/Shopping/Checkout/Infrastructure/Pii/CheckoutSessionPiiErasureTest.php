<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\Pii;

use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
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
        $erased = $this->storedEventOf(
            CheckoutSessionOpened::class,
            static fn (CheckoutSessionOpened $event): bool => $event->id === $checkoutSession->id->toString(),
        );

        // Then
        self::assertSame($this->erasedPostalAddress(), PostalAddressMapper::toArray($erased->shippingAddress));
    }

    #[Test]
    public function itCryptoShredsBillingAddressOnErasure(): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()->create();
        $this->store($checkoutSession);

        // When
        $this->cipherKeyStore->removeWithSubjectId($checkoutSession->id->toString());
        $erased = $this->storedEventOf(
            CheckoutSessionOpened::class,
            static fn (CheckoutSessionOpened $event): bool => $event->id === $checkoutSession->id->toString(),
        );

        // Then
        self::assertSame($this->erasedPostalAddress(), PostalAddressMapper::toArray($erased->billingAddress));
    }

    #[Test]
    public function itCryptoShredsShippingAddressOnCheckoutSessionCompletedErasure(): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()->completed()->create();
        $this->store($checkoutSession);

        // When
        $this->cipherKeyStore->removeWithSubjectId($checkoutSession->id->toString());
        $erased = $this->storedEventOf(
            CheckoutSessionCompleted::class,
            static fn (CheckoutSessionCompleted $event): bool => $event->id === $checkoutSession->id->toString(),
        );

        // Then
        self::assertSame($this->erasedPostalAddress(), PostalAddressMapper::toArray($erased->shippingAddress));
    }

    #[Test]
    public function itCryptoShredsBillingAddressOnCheckoutSessionCompletedErasure(): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()->completed()->create();
        $this->store($checkoutSession);

        // When
        $this->cipherKeyStore->removeWithSubjectId($checkoutSession->id->toString());
        $erased = $this->storedEventOf(
            CheckoutSessionCompleted::class,
            static fn (CheckoutSessionCompleted $event): bool => $event->id === $checkoutSession->id->toString(),
        );

        // Then
        self::assertSame($this->erasedPostalAddress(), PostalAddressMapper::toArray($erased->billingAddress));
    }

    #[Test]
    public function itCryptoShredsCheckoutSessionCompletedIntegrationEventShippingAddressOnErasure(): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()->completed()->create();
        $this->store($checkoutSession);

        // When
        $this->cipherKeyStore->removeWithSubjectId($checkoutSession->id->toString());
        $erased = $this->storedEventOf(
            CheckoutSessionCompletedIntegrationEvent::class,
            static fn (CheckoutSessionCompletedIntegrationEvent $event): bool => $event->checkoutSessionId === $checkoutSession->id->toString(),
        );

        // Then
        self::assertSame($this->erasedPostalAddress(), $erased->shippingAddress);
    }

    #[Test]
    public function itCryptoShredsCheckoutSessionCompletedIntegrationEventBillingAddressOnErasure(): void
    {
        // Given
        $checkoutSession = CheckoutSessionFactory::new()->completed()->create();
        $this->store($checkoutSession);

        // When
        $this->cipherKeyStore->removeWithSubjectId($checkoutSession->id->toString());
        $erased = $this->storedEventOf(
            CheckoutSessionCompletedIntegrationEvent::class,
            static fn (CheckoutSessionCompletedIntegrationEvent $event): bool => $event->checkoutSessionId === $checkoutSession->id->toString(),
        );

        // Then
        self::assertSame($this->erasedPostalAddress(), $erased->billingAddress);
    }

    /**
     * @return array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}}
     */
    private function erasedPostalAddress(): array
    {
        return PostalAddressMapper::toArray(PostalAddress::of('erased', Address::of('erased', '00000', 'erased', 'ZZ')));
    }
}
