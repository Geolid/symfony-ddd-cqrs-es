<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Infrastructure\Pii;

use Fulfilment\Shipping\Domain\Event\ShipmentRequested;
use Fulfilment\Tests\Shipping\Support\Factory\ShipmentFactory;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\Pii\ErasedPostalAddress;
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

        // Then
        $erased = $this->storedEventOf(ShipmentRequested::class, $shipment->id->toString());
        self::assertSame(PostalAddressMapper::toArray((new ErasedPostalAddress())()), PostalAddressMapper::toArray($erased->origin));
        self::assertSame(PostalAddressMapper::toArray((new ErasedPostalAddress())()), PostalAddressMapper::toArray($erased->destination));
    }
}
