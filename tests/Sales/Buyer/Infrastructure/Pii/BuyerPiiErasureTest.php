<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Infrastructure\Pii;

use Patchlevel\EventSourcing\Serializer\EventSerializer;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Sales\Buyer\Application\IntegrationEvent\BuyerBillingAddressDefined\BuyerBillingAddressDefinedIntegrationEvent;
use Sales\Buyer\Application\IntegrationEvent\BuyerShippingAddressDefined\BuyerShippingAddressDefinedIntegrationEvent;
use Sales\Buyer\Domain\Event\BuyerBillingAddressDefined;
use Sales\Buyer\Domain\Event\BuyerRegistered;
use Sales\Buyer\Domain\Event\BuyerShippingAddressDefined;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Shared\Domain\Pii\ErasedFieldSentinel;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

final class BuyerPiiErasureTest extends AbstractIntegrationTestCase
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
        $buyer = BuyerBuilder::new()->create();
        $this->store($buyer);
        $serialized = $this->serializedEventOf(
            BuyerRegistered::class,
            static fn (BuyerRegistered $event): bool => $event->id === $buyer->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($buyer->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(BuyerRegistered::class, $rehydrated);
        $sentinel = new ErasedFieldSentinel('%s@erased.invalid');
        $expectedEmail = $sentinel($buyer->id->toString());
        self::assertSame($expectedEmail, $rehydrated->email->value);
    }

    #[Test]
    public function itCryptoShredsShippingAddressOnErasure(): void
    {
        // Given
        $buyer = BuyerBuilder::new()
            ->shippingAddressDefined()
            ->create();
        $this->store($buyer);
        $serialized = $this->serializedEventOf(
            BuyerShippingAddressDefined::class,
            static fn (BuyerShippingAddressDefined $event): bool => $event->id === $buyer->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($buyer->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(BuyerShippingAddressDefined::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress()->toArray(), $rehydrated->postalAddress->toArray());
    }

    #[Test]
    public function itCryptoShredsBillingAddressOnErasure(): void
    {
        // Given
        $buyer = BuyerBuilder::new()
            ->billingAddressDefined()
            ->create();
        $this->store($buyer);
        $serialized = $this->serializedEventOf(
            BuyerBillingAddressDefined::class,
            static fn (BuyerBillingAddressDefined $event): bool => $event->id === $buyer->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($buyer->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(BuyerBillingAddressDefined::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress()->toArray(), $rehydrated->postalAddress->toArray());
    }

    #[Test]
    public function itCryptoShredsBuyerShippingAddressDefinedIntegrationEventOnErasure(): void
    {
        // Given
        $buyer = BuyerBuilder::new()
            ->shippingAddressDefined()
            ->create();
        $this->store($buyer);
        $serialized = $this->serializedEventOf(
            BuyerShippingAddressDefinedIntegrationEvent::class,
            static fn (BuyerShippingAddressDefinedIntegrationEvent $event): bool => $event->buyerId === $buyer->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($buyer->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(BuyerShippingAddressDefinedIntegrationEvent::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress()->toArray(), $rehydrated->postalAddress);
    }

    #[Test]
    public function itCryptoShredsBuyerBillingAddressDefinedIntegrationEventOnErasure(): void
    {
        // Given
        $buyer = BuyerBuilder::new()
            ->billingAddressDefined()
            ->create();
        $this->store($buyer);
        $serialized = $this->serializedEventOf(
            BuyerBillingAddressDefinedIntegrationEvent::class,
            static fn (BuyerBillingAddressDefinedIntegrationEvent $event): bool => $event->buyerId === $buyer->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($buyer->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(BuyerBillingAddressDefinedIntegrationEvent::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress()->toArray(), $rehydrated->postalAddress);
    }

    private function erasedPostalAddress(): PostalAddress
    {
        return PostalAddress::of('erased', Address::of('erased', '00000', 'erased', 'ZZ'));
    }
}
