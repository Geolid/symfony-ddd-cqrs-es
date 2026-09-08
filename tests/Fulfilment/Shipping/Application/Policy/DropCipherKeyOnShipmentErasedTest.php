<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Policy;

use Fulfilment\Shipping\Application\Policy\DropCipherKeyOnShipmentErased;
use Fulfilment\Shipping\Domain\Event\ShipmentErased;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use Patchlevel\Hydrator\Extension\Cryptography\Cipher\CipherKey;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyNotExists;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class DropCipherKeyOnShipmentErasedTest extends AbstractIntegrationTestCase
{
    private CipherKeyStore $cipherKeyStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cipherKeyStore = $this->service(CipherKeyStore::class);
    }

    #[Test]
    public function itDrops(): void
    {
        // Given
        $shipment = ShipmentBuilder::new()->create();
        $this->store($shipment);
        $shipmentId = $shipment->id->toString();
        $now = Clock::get()->now();
        $this->cipherKeyStore->store(new CipherKey(
            id: Uuid::uuid7()->toString(),
            subjectId: $shipmentId,
            key: 'fake-key',
            method: 'aes256',
            createdAt: $now,
        ));

        // When
        $this->trigger(DropCipherKeyOnShipmentErased::class, new ShipmentErased($shipmentId, $now));

        // Then
        $this->expectException(CipherKeyNotExists::class);
        $this->cipherKeyStore->currentKeyFor($shipmentId);
    }
}
