<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\ManifestShipmentReturn;

use Fulfilment\Shipment\Application\Command\ManifestShipmentReturn\ManifestShipmentReturn;
use Fulfilment\Shipment\Application\Exception\TrackingReferenceAlreadyTakenException;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Fulfilment\Shipment\Domain\Exception\ShipmentAlreadyTrackedException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentUniqueKey;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Shared\Domain\ValueObject\UniqueKey;
use Support\AbstractIntegrationTestCase;

final class ManifestShipmentReturnHandlerTest extends AbstractIntegrationTestCase
{
    private UniqueValueRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uniqueValues = $this->service(UniqueValueRegistryInterface::class);
    }

    #[Test]
    public function itManifestsReturnWhenRequested(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->store();

        // When
        $this->dispatch(new ManifestShipmentReturn($shipment->id()->toString(), 'ACME-RETURN-1'));

        // Then
        $results = iterator_to_array($this->service(ShipmentFinderInterface::class), false);
        self::assertCount(1, $results);
        self::assertSame(ShipmentStatus::RETURN_MANIFESTED, $results[0]->status);
        self::assertSame('ACME-RETURN-1', $results[0]->returnTrackingReference);
    }

    #[Test]
    public function itIgnoresWithSameReturnTrackingReference(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested('ACME-RETURN-1')->store();
        $this->uniqueValues->reserve(UniqueKey::for(ShipmentUniqueKey::RETURN_TRACKING_REFERENCE), 'ACME-RETURN-1', $shipment->id()->toString());

        // When
        $this->dispatch(new ManifestShipmentReturn($shipment->id()->toString(), 'ACME-RETURN-1'));

        // Then
        $results = iterator_to_array($this->service(ShipmentFinderInterface::class), false);
        self::assertSame(ShipmentStatus::RETURN_MANIFESTED, $results[0]->status);
    }

    #[Test]
    public function itFailsWhenAlreadyTrackedUnderAnotherReturnReference(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested('ACME-RETURN-1')->store();

        // Then
        $this->expectException(ShipmentAlreadyTrackedException::class);

        // When
        $this->dispatch(new ManifestShipmentReturn($shipment->id()->toString(), 'ACME-RETURN-OTHER'));
    }

    #[Test]
    public function itFailsWhenReturnTrackingReferenceAlreadyTaken(): void
    {
        // Given
        $this->uniqueValues->reserve(UniqueKey::for(ShipmentUniqueKey::RETURN_TRACKING_REFERENCE), 'ACME-RETURN-1', Uuid::uuid7()->toString());
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->store();

        // Then
        $this->expectException(TrackingReferenceAlreadyTakenException::class);

        // When
        $this->dispatch(new ManifestShipmentReturn($shipment->id()->toString(), 'ACME-RETURN-1'));
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();

        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->dispatch(new ManifestShipmentReturn($id, 'ACME-RETURN-1'));
    }
}
