<?php

declare(strict_types=1);

namespace Shopping\Tests\Checkout\Infrastructure\Pii;

use Patchlevel\EventSourcing\Serializer\EventSerializer;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\Pii\ErasedFieldSentinel;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Shopping\Checkout\Application\IntegrationEvent\ShopperBillingAddressDefined\ShopperBillingAddressDefinedIntegrationEvent;
use Shopping\Checkout\Application\IntegrationEvent\ShopperShippingAddressDefined\ShopperShippingAddressDefinedIntegrationEvent;
use Shopping\Checkout\Domain\Event\ShopperBillingAddressDefined;
use Shopping\Checkout\Domain\Event\ShopperRegistered;
use Shopping\Checkout\Domain\Event\ShopperShippingAddressDefined;
use Shopping\Tests\Checkout\Support\Builder\ShopperBuilder;
use Support\TestCase\AbstractIntegrationTestCase;

final class ShopperPiiErasureTest extends AbstractIntegrationTestCase
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
    public function itCryptoShredsEmailOnErasure(): void
    {
        // Given
        $shopper = ShopperBuilder::new()->create();
        $this->store($shopper);
        $serialized = $this->serializedEventOf(
            ShopperRegistered::class,
            static fn (ShopperRegistered $event): bool => $event->id === $shopper->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($shopper->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(ShopperRegistered::class, $rehydrated);
        $sentinel = new ErasedFieldSentinel('%s@erased.invalid');
        $expectedEmail = $sentinel($shopper->id->toString());
        self::assertSame($expectedEmail, $rehydrated->email->value);
    }

    #[Test]
    public function itCryptoShredsShippingAddressOnErasure(): void
    {
        // Given
        $shopper = ShopperBuilder::new()
            ->shippingAddressDefined()
            ->create();
        $this->store($shopper);
        $serialized = $this->serializedEventOf(
            ShopperShippingAddressDefined::class,
            static fn (ShopperShippingAddressDefined $event): bool => $event->id === $shopper->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($shopper->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(ShopperShippingAddressDefined::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress(), PostalAddressMapper::toArray($rehydrated->postalAddress));
    }

    #[Test]
    public function itCryptoShredsBillingAddressOnErasure(): void
    {
        // Given
        $shopper = ShopperBuilder::new()
            ->billingAddressDefined()
            ->create();
        $this->store($shopper);
        $serialized = $this->serializedEventOf(
            ShopperBillingAddressDefined::class,
            static fn (ShopperBillingAddressDefined $event): bool => $event->id === $shopper->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($shopper->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(ShopperBillingAddressDefined::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress(), PostalAddressMapper::toArray($rehydrated->postalAddress));
    }

    #[Test]
    public function itCryptoShredsShopperShippingAddressDefinedIntegrationEventOnErasure(): void
    {
        // Given
        $shopper = ShopperBuilder::new()
            ->shippingAddressDefined()
            ->create();
        $this->store($shopper);
        $serialized = $this->serializedEventOf(
            ShopperShippingAddressDefinedIntegrationEvent::class,
            static fn (ShopperShippingAddressDefinedIntegrationEvent $event): bool => $event->shopperId === $shopper->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($shopper->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(ShopperShippingAddressDefinedIntegrationEvent::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress(), $rehydrated->postalAddress);
    }

    #[Test]
    public function itCryptoShredsShopperBillingAddressDefinedIntegrationEventOnErasure(): void
    {
        // Given
        $shopper = ShopperBuilder::new()
            ->billingAddressDefined()
            ->create();
        $this->store($shopper);
        $serialized = $this->serializedEventOf(
            ShopperBillingAddressDefinedIntegrationEvent::class,
            static fn (ShopperBillingAddressDefinedIntegrationEvent $event): bool => $event->shopperId === $shopper->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($shopper->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(ShopperBillingAddressDefinedIntegrationEvent::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress(), $rehydrated->postalAddress);
    }

    /**
     * @return array{recipientName: string, address: array{street: string, postalCode: string, city: string, countryCode: string}}
     */
    private function erasedPostalAddress(): array
    {
        return PostalAddressMapper::toArray(PostalAddress::of('erased', Address::of('erased', '00000', 'erased', 'ZZ')));
    }
}
