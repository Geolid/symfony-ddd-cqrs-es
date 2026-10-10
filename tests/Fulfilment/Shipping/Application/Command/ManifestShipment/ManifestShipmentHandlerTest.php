<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Command\ManifestShipment;

use Fulfilment\Shipping\Application\Command\ManifestShipment\Exception\ShipmentTrackingNumberAlreadyInUseException;
use Fulfilment\Shipping\Application\Command\ManifestShipment\ManifestShipment;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Fulfilment\Shipping\Application\ShippingUniqueKey;
use Fulfilment\Shipping\Domain\Exception\ShipmentAlreadyTrackedException;
use Fulfilment\Shipping\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipping\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Tests\Shipping\Support\Factory\ShipmentFactory;
use Fulfilment\Tests\Shipping\Support\Factory\TrackingNumberFactory;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

final class ManifestShipmentHandlerTest extends AbstractIntegrationTestCase
{
    private UniquenessRegistryInterface $uniqueness;

    private ShipmentFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uniqueness = $this->service(UniquenessRegistryInterface::class);
        $this->finder = $this->service(ShipmentFinderInterface::class);
    }

    #[Test]
    public function itManifestsWhenPrepared(): void
    {
        // Given
        $shipment = ShipmentFactory::new()->prepared()->create();
        $this->store($shipment);
        $trackingNumber = TrackingNumberFactory::new()->create();

        // When
        $this->dispatch(new ManifestShipment($shipment->id->toString(), $trackingNumber->value));

        // Then
        $result = $this->finder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::MANIFESTED, $result->status);
    }

    #[Test]
    public function itIgnoresWithSameTrackingNumber(): void
    {
        // Given
        $trackingNumber = TrackingNumberFactory::new()->create();
        $shipment = ShipmentFactory::new()->prepared()->manifested($trackingNumber)->create();
        $this->store($shipment);
        $this->uniqueness->claim(UniqueKey::for(ShippingUniqueKey::TRACKING_NUMBER), $trackingNumber->value, $shipment->id->toString());

        // When
        $this->dispatch(new ManifestShipment($shipment->id->toString(), $trackingNumber->value));

        // Then
        $result = $this->finder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::MANIFESTED, $result->status);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = ShipmentFactory::new()->create()->id->toString();

        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->dispatch(new ManifestShipment($id, TrackingNumberFactory::new()->create()->value));
    }

    #[Test]
    public function itFailsWhenAlreadyTrackedUnderAnotherReference(): void
    {
        // Given
        $shipment = ShipmentFactory::new()->prepared()->manifested()->create();
        $this->store($shipment);

        // Then
        $this->expectException(ShipmentAlreadyTrackedException::class);

        // When
        $this->dispatch(new ManifestShipment($shipment->id->toString(), TrackingNumberFactory::new()->create()->value));
    }

    #[Test]
    public function itFailsWhenNotPrepared(): void
    {
        // Given
        $shipment = ShipmentFactory::new()->create();
        $this->store($shipment);

        // Then
        $this->expectException(ShipmentInvalidTransitionException::class);

        // When
        $this->dispatch(new ManifestShipment($shipment->id->toString(), TrackingNumberFactory::new()->create()->value));
    }

    #[Test]
    public function itFailsWhenTrackingNumberAlreadyInUse(): void
    {
        // Given
        $trackingNumber = TrackingNumberFactory::new()->create();
        $this->uniqueness->claim(UniqueKey::for(ShippingUniqueKey::TRACKING_NUMBER), $trackingNumber->value, ShipmentFactory::new()->create()->id->toString());
        $shipment = ShipmentFactory::new()->prepared()->create();
        $this->store($shipment);

        // Then
        $this->expectException(ShipmentTrackingNumberAlreadyInUseException::class);

        // When
        $this->dispatch(new ManifestShipment($shipment->id->toString(), $trackingNumber->value));
    }
}
