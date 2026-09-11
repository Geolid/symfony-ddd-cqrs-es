<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\Pii;

use Patchlevel\EventSourcing\Serializer\EventSerializer;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Shopping\Checkout\Application\IntegrationEvent\CheckoutSessionOpened\CheckoutSessionOpenedIntegrationEvent;
use Shopping\Checkout\Domain\CheckoutSession\Event\CheckoutSessionOpened;
use Shopping\Tests\Checkout\Support\Builder\CheckoutSessionBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class CheckoutSessionPiiErasureTest extends AbstractIntegrationTestCase
{
    private CipherKeyStore $cipherKeyStore;

    private EventSerializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipherKeyStore = $this->service(CipherKeyStore::class);
        $this->serializer = $this->service(EventSerializer::class);
    }

    #[Test]
    public function itCryptoShredsShippingAddressOnErasure(): void
    {
        // Given
        $checkoutSession = CheckoutSessionBuilder::new()->create();
        $this->store($checkoutSession);
        $serialized = $this->serializedEventOf(
            CheckoutSessionOpened::class,
            static fn (CheckoutSessionOpened $event): bool => $event->id === $checkoutSession->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($checkoutSession->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(CheckoutSessionOpened::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress(), PostalAddressMapper::toArray($rehydrated->shippingAddress));
    }

    #[Test]
    public function itCryptoShredsBillingAddressOnErasure(): void
    {
        // Given
        $checkoutSession = CheckoutSessionBuilder::new()->create();
        $this->store($checkoutSession);
        $serialized = $this->serializedEventOf(
            CheckoutSessionOpened::class,
            static fn (CheckoutSessionOpened $event): bool => $event->id === $checkoutSession->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($checkoutSession->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(CheckoutSessionOpened::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress(), PostalAddressMapper::toArray($rehydrated->billingAddress));
    }

    #[Test]
    public function itCryptoShredsCheckoutSessionOpenedIntegrationEventShippingAddressOnErasure(): void
    {
        // Given
        $checkoutSession = CheckoutSessionBuilder::new()->create();
        $this->store($checkoutSession);
        $serialized = $this->serializedEventOf(
            CheckoutSessionOpenedIntegrationEvent::class,
            static fn (CheckoutSessionOpenedIntegrationEvent $event): bool => $event->checkoutSessionId === $checkoutSession->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($checkoutSession->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(CheckoutSessionOpenedIntegrationEvent::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress(), $rehydrated->shippingAddress);
    }

    #[Test]
    public function itCryptoShredsCheckoutSessionOpenedIntegrationEventBillingAddressOnErasure(): void
    {
        // Given
        $checkoutSession = CheckoutSessionBuilder::new()->create();
        $this->store($checkoutSession);
        $serialized = $this->serializedEventOf(
            CheckoutSessionOpenedIntegrationEvent::class,
            static fn (CheckoutSessionOpenedIntegrationEvent $event): bool => $event->checkoutSessionId === $checkoutSession->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($checkoutSession->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(CheckoutSessionOpenedIntegrationEvent::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress(), $rehydrated->billingAddress);
    }

    /**
     * @return array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}}
     */
    private function erasedPostalAddress(): array
    {
        return PostalAddressMapper::toArray(PostalAddress::of('erased', Address::of('erased', '00000', 'erased', 'ZZ')));
    }
}
