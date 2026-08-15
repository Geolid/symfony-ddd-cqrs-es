<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\ManifestShipment;

use Fulfilment\Shipment\Application\Command\ManifestShipment\ManifestShipment;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Fulfilment\Shipment\Domain\Exception\ShipmentAlreadyTrackedException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class ManifestShipmentHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itManifestsAPreparedShipment(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->store();

        // When
        $this->dispatch(new ManifestShipment($shipment->id()->toString(), 'ACME-4Q7X2K9'));

        // Then
        $results = iterator_to_array($this->service(ShipmentFinderInterface::class), false);
        self::assertCount(1, $results);
        self::assertSame(ShipmentStatus::MANIFESTED, $results[0]->status);
        self::assertSame('ACME-4Q7X2K9', $results[0]->trackingReference);
    }

    #[Test]
    public function itFailsWhenTheShipmentIsAlreadyTrackedUnderAnotherReference(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested('ACME-4Q7X2K9')->store();

        // Then
        $this->expectException(ShipmentAlreadyTrackedException::class);

        // When
        $this->dispatch(new ManifestShipment($shipment->id()->toString(), 'ACME-OTHER'));
    }

    #[Test]
    public function itFailsWhenTheShipmentDoesNotExist(): void
    {
        // Given
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();

        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->dispatch(new ManifestShipment($id, 'ACME-4Q7X2K9'));
    }
}
