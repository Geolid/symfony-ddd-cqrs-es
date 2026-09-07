<?php

declare(strict_types=1);

namespace Sales\Tests\Buyer\Infrastructure\Gdpr;

use Patchlevel\EventSourcing\Serializer\EventSerializer;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Sales\Buyer\Application\IntegrationEvent\BuyerPostalAddressDefined\BuyerPostalAddressDefinedIntegrationEvent;
use Sales\Buyer\Domain\Event\BuyerPostalAddressDefined;
use Sales\Buyer\Domain\Event\BuyerRegistered;
use Sales\Tests\Buyer\Support\Builder\BuyerBuilder;
use Shared\Domain\Gdpr\ErasedFieldSentinel;
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
    public function itCryptoShredsPostalAddressOnErasure(): void
    {
        // Given
        $buyer = BuyerBuilder::new()
            ->postalAddressDefined()
            ->create();
        $this->store($buyer);
        $serialized = $this->serializedEventOf(
            BuyerPostalAddressDefined::class,
            static fn (BuyerPostalAddressDefined $event): bool => $event->id === $buyer->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($buyer->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(BuyerPostalAddressDefined::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress()->toArray(), $rehydrated->postalAddress->toArray());
    }

    #[Test]
    public function itCryptoShredsBuyerPostalAddressDefinedIntegrationEventOnErasure(): void
    {
        // Given
        $buyer = BuyerBuilder::new()
            ->postalAddressDefined()
            ->create();
        $this->store($buyer);
        $serialized = $this->serializedEventOf(
            BuyerPostalAddressDefinedIntegrationEvent::class,
            static fn (BuyerPostalAddressDefinedIntegrationEvent $event): bool => $event->buyerId === $buyer->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($buyer->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(BuyerPostalAddressDefinedIntegrationEvent::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress()->toArray(), $rehydrated->postalAddress);
    }

    private function erasedPostalAddress(): PostalAddress
    {
        return PostalAddress::of('erased', Address::of('erased', '00000', 'erased', 'ZZ'));
    }
}
