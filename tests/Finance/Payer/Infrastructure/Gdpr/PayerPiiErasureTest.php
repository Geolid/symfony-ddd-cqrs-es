<?php

declare(strict_types=1);

namespace Finance\Tests\Payer\Infrastructure\Gdpr;

use Finance\Payer\Application\IntegrationEvent\PayerPostalAddressDefined\PayerPostalAddressDefinedIntegrationEvent;
use Finance\Payer\Domain\Event\PayerPostalAddressDefined;
use Finance\Tests\Payer\Support\Builder\PayerBuilder;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

final class PayerPiiErasureTest extends AbstractIntegrationTestCase
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
    public function itCryptoShredsPostalAddressOnErasure(): void
    {
        // Given
        $payer = PayerBuilder::new()
            ->postalAddressDefined()
            ->create();
        $this->store($payer);
        $serialized = $this->serializedEventOf(
            PayerPostalAddressDefined::class,
            static fn (PayerPostalAddressDefined $event): bool => $event->id === $payer->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($payer->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(PayerPostalAddressDefined::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress()->toArray(), $rehydrated->postalAddress->toArray());
    }

    #[Test]
    public function itCryptoShredsPayerPostalAddressDefinedIntegrationEventOnErasure(): void
    {
        // Given
        $payer = PayerBuilder::new()
            ->postalAddressDefined()
            ->create();
        $this->store($payer);
        $serialized = $this->serializedEventOf(
            PayerPostalAddressDefinedIntegrationEvent::class,
            static fn (PayerPostalAddressDefinedIntegrationEvent $event): bool => $event->payerId === $payer->id->toString(),
        );

        // When
        $this->cipherKeyStore->removeWithSubjectId($payer->id->toString());

        // Then
        $rehydrated = $this->serializer->deserialize($serialized);
        self::assertInstanceOf(PayerPostalAddressDefinedIntegrationEvent::class, $rehydrated);
        self::assertSame($this->erasedPostalAddress()->toArray(), $rehydrated->postalAddress);
    }

    private function erasedPostalAddress(): PostalAddress
    {
        return PostalAddress::of('erased', Address::of('erased', '00000', 'erased', 'ZZ'));
    }
}
