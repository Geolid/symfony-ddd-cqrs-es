<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\ManifestShipment;

use Fulfilment\Shipment\Application\Command\ManifestShipment\ManifestShipment;
use Fulfilment\Shipment\Application\Exception\TrackingReferenceAlreadyTakenException;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Shipment\Domain\Exception\ShipmentAlreadyTrackedException;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentUniqueKey;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\AbstractIntegrationTestCase;
use Support\SeededFaker;

final class ManifestShipmentHandlerTest extends AbstractIntegrationTestCase
{
    private UniqueValueRegistryInterface $uniqueValues;

    private ShipmentFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uniqueValues = $this->service(UniqueValueRegistryInterface::class);
        $this->finder = $this->service(ShipmentFinderInterface::class);
    }

    #[Test]
    public function itManifestsWhenPrepared(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->create();
        $this->store($shipment);
        $trackingReference = SeededFaker::get()->regexify('ACME-[A-Z0-9]{8}');

        // When
        $this->dispatch(new ManifestShipment($shipment->id->toString(), $trackingReference));

        // Then
        $result = $this->finder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::MANIFESTED, $result->status);
    }

    #[Test]
    public function itIgnoresWithSameTrackingReference(): void
    {
        // Given
        $trackingReference = SeededFaker::get()->regexify('ACME-[A-Z0-9]{8}');
        $shipment = ShipmentTestFactory::new()->prepared()->manifested($trackingReference)->create();
        $this->store($shipment);
        $this->uniqueValues->reserve(UniqueKey::for(ShipmentUniqueKey::TRACKING_REFERENCE), $trackingReference, $shipment->id->toString());

        // When
        $this->dispatch(new ManifestShipment($shipment->id->toString(), $trackingReference));

        // Then
        $result = $this->finder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::MANIFESTED, $result->status);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = ShipmentTestFactory::new()->create()->id->toString();

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
    public function itFailsWhenNotPrepared(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->create();
        $this->store($shipment);

        // Then
        $this->expectException(ShipmentInvalidTransitionException::class);

        // When
        $this->dispatch(new ManifestShipment($shipment->id->toString(), SeededFaker::get()->regexify('ACME-[A-Z0-9]{8}')));
    }

    #[Test]
    public function itFailsWhenTrackingReferenceAlreadyTaken(): void
    {
        // Given
        $trackingReference = SeededFaker::get()->regexify('ACME-[A-Z0-9]{8}');
        $this->uniqueValues->reserve(UniqueKey::for(ShipmentUniqueKey::TRACKING_REFERENCE), $trackingReference, Uuid::uuid7()->toString());
        $shipment = ShipmentTestFactory::new()->prepared()->create();
        $this->store($shipment);

        // Then
        $this->expectException(TrackingReferenceAlreadyTakenException::class);

        // When
        $this->dispatch(new ManifestShipment($shipment->id->toString(), $trackingReference));
    }
}
