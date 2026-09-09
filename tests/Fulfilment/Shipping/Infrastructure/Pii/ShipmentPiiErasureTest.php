<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Infrastructure\Pii;

use Fulfilment\Shipping\Domain\Event\ShipmentRequested;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\PostalAddress;
use Support\TestCase\AbstractIntegrationTestCase;

final class ShipmentPiiErasureTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCryptoShredsFrozenAddressesOnErasure(): void
    {
        // Given
        $shipment = ShipmentBuilder::new()->create();
        $this->store($shipment);
        $serialized = $this->serializedEventOf(
            ShipmentRequested::class,
            static fn (ShipmentRequested $event): bool => $event->id === $shipment->id->toString(),
        );

        // When
        $this->service(CipherKeyStore::class)->removeWithSubjectId($shipment->id->toString());

        // Then
        $rehydrated = $this->service(EventSerializer::class)->deserialize($serialized);
        self::assertInstanceOf(ShipmentRequested::class, $rehydrated);
        $erasedAddress = PostalAddressMapper::toArray(PostalAddress::of('erased', Address::of('erased', '00000', 'erased', 'ZZ')));
        self::assertSame($erasedAddress, PostalAddressMapper::toArray($rehydrated->origin));
        self::assertSame($erasedAddress, PostalAddressMapper::toArray($rehydrated->destination));
    }
}
