<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\ManifestShipment;

use Fulfilment\Shipment\Application\Command\ManifestShipment\ManifestShipment;
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

final class ManifestShipmentHandlerTest extends AbstractIntegrationTestCase
{
    private UniqueValueRegistryInterface $uniqueValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uniqueValues = $this->service(UniqueValueRegistryInterface::class);
    }

    #[Test]
    public function itManifestsPrepared(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->create();
        $this->store($shipment);
        $trackingReference = 'ACME-4Q7X2K9';

        // When
        $this->dispatch(new ManifestShipment($shipment->id->toString(), $trackingReference));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofTrackingReference($trackingReference);
        self::assertSame(ShipmentStatus::MANIFESTED, $result->status);
    }

    #[Test]
    public function itIgnoresWithSameTrackingReference(): void
    {
        // Given
        $trackingReference = 'ACME-4Q7X2K9';
        $shipment = ShipmentTestFactory::new()->prepared()->manifested($trackingReference)->create();
        $this->store($shipment);
        $this->uniqueValues->reserve(UniqueKey::for(ShipmentUniqueKey::TRACKING_REFERENCE), $trackingReference, $shipment->id->toString());

        // When
        $this->dispatch(new ManifestShipment($shipment->id->toString(), $trackingReference));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofTrackingReference($trackingReference);
        self::assertSame(ShipmentStatus::MANIFESTED, $result->status);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();

        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->dispatch(new ManifestShipment($id, 'ACME-4Q7X2K9'));
    }

    #[Test]
    public function itFailsWhenAlreadyTrackedUnderAnotherReference(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested('ACME-4Q7X2K9')->create();
        $this->store($shipment);

        // Then
        $this->expectException(ShipmentAlreadyTrackedException::class);

        // When
        $this->dispatch(new ManifestShipment($shipment->id->toString(), 'ACME-OTHER'));
    }

    #[Test]
    public function itFailsWhenTrackingReferenceAlreadyTaken(): void
    {
        // Given
        $this->uniqueValues->reserve(UniqueKey::for(ShipmentUniqueKey::TRACKING_REFERENCE), 'ACME-4Q7X2K9', Uuid::uuid7()->toString());
        $shipment = ShipmentTestFactory::new()->prepared()->create();
        $this->store($shipment);

        // Then
        $this->expectException(TrackingReferenceAlreadyTakenException::class);

        // When
        $this->dispatch(new ManifestShipment($shipment->id->toString(), 'ACME-4Q7X2K9'));
    }
}
