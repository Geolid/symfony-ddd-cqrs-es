<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Infrastructure\Pii;

use Fulfilment\Shipping\Domain\Event\ShipmentRequested;
use Fulfilment\Tests\Shipping\Support\Factory\ShipmentFactory;
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
        $shipment = ShipmentFactory::new()->create();
        $this->store($shipment);

        // When
        $this->service(CipherKeyStore::class)->removeWithSubjectId($shipment->id->toString());
        $erased = $this->storedEventOf(
            ShipmentRequested::class,
            static fn (ShipmentRequested $event): bool => $event->id->equals($shipment->id),
        );

        // Then
        $erasedAddress = PostalAddressMapper::toArray(PostalAddress::of('erased', Address::of('erased', '00000', 'erased', 'ZZ')));
        self::assertSame($erasedAddress, PostalAddressMapper::toArray($erased->origin));
        self::assertSame($erasedAddress, PostalAddressMapper::toArray($erased->destination));
    }
}
