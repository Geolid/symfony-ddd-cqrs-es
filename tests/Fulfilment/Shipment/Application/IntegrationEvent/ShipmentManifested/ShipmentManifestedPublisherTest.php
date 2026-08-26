<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\IntegrationEvent\ShipmentManifested;

use Fulfilment\Shipment\Application\IntegrationEvent\ShipmentManifested\ShipmentManifestedIntegrationEvent;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class ShipmentManifestedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()->withOrderId($orderId)->prepared()->manifested('ACME-4Q7X2K9')->create();

        // When
        $this->store($shipment);

        // Then
        $event = $this->publishedEventOf(ShipmentManifestedIntegrationEvent::class);
        self::assertSame($shipment->id->toString(), $event->shipmentId);
        self::assertSame($orderId, $event->orderId);
        self::assertSame('ACME-4Q7X2K9', $event->trackingReference);
    }
}
