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
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
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
        $returnTrackingReference = 'ACME-RETURN-1';
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new ManifestShipmentReturn($shipment->id->toString(), $returnTrackingReference));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofReturnTrackingReference($returnTrackingReference);
        self::assertSame(ShipmentStatus::RETURN_MANIFESTED, $result->status);
    }

    #[Test]
    public function itIgnoresWithSameReturnTrackingReference(): void
    {
        // Given
        $returnTrackingReference = 'ACME-RETURN-1';
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested($returnTrackingReference)->create();
        $this->store($shipment);
        $this->uniqueValues->reserve(UniqueKey::for(ShipmentUniqueKey::RETURN_TRACKING_REFERENCE), $returnTrackingReference, $shipment->id->toString());

        // When
        $this->dispatch(new ManifestShipmentReturn($shipment->id->toString(), $returnTrackingReference));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofReturnTrackingReference($returnTrackingReference);
        self::assertSame(ShipmentStatus::RETURN_MANIFESTED, $result->status);
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

    #[Test]
    public function itFailsWhenAlreadyTrackedUnderAnotherReturnReference(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested('ACME-RETURN-1')->create();
        $this->store($shipment);

        // Then
        $this->expectException(ShipmentAlreadyTrackedException::class);

        // When
        $this->dispatch(new ManifestShipmentReturn($shipment->id->toString(), 'ACME-RETURN-OTHER'));
    }

    #[Test]
    public function itFailsWhenReturnTrackingReferenceAlreadyTaken(): void
    {
        // Given
        $this->uniqueValues->reserve(UniqueKey::for(ShipmentUniqueKey::RETURN_TRACKING_REFERENCE), 'ACME-RETURN-1', Uuid::uuid7()->toString());
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->create();
        $this->store($shipment);

        // Then
        $this->expectException(TrackingReferenceAlreadyTakenException::class);

        // When
        $this->dispatch(new ManifestShipmentReturn($shipment->id->toString(), 'ACME-RETURN-1'));
    }
}
