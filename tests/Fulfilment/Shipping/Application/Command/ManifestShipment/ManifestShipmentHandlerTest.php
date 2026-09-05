<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Command\ManifestShipment;

use Fulfilment\Shipping\Application\Command\ManifestShipment\Exception\ShipmentTrackingNumberAlreadyTakenException;
use Fulfilment\Shipping\Application\Command\ManifestShipment\ManifestShipment;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Fulfilment\Shipping\Domain\Exception\ShipmentAlreadyTrackedException;
use Fulfilment\Shipping\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipping\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentUniqueKey;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniqueValueRegistryInterface;
use Support\TestCase\AbstractIntegrationTestCase;

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
        $shipment = ShipmentBuilder::new()->prepared()->create();
        $this->store($shipment);
        $trackingNumber = ShipmentBuilder::new()->manifested()['trackingNumber']->value;

        // When
        $this->dispatch(new ManifestShipment($shipment->id->toString(), $trackingNumber));

        // Then
        $result = $this->finder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::MANIFESTED, $result->status);
    }

    #[Test]
    public function itIgnoresWithSameTrackingNumber(): void
    {
        // Given
        $trackingNumber = ShipmentBuilder::new()->manifested()['trackingNumber']->value;
        $shipment = ShipmentBuilder::new()->prepared()->manifested($trackingNumber)->create();
        $this->store($shipment);
        $this->uniqueValues->reserve(UniqueKey::for(ShipmentUniqueKey::TRACKING_NUMBER), $trackingNumber, $shipment->id->toString());

        // When
        $this->dispatch(new ManifestShipment($shipment->id->toString(), $trackingNumber));

        // Then
        $result = $this->finder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::MANIFESTED, $result->status);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = ShipmentBuilder::new()->create()->id->toString();

        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->dispatch(new ManifestShipment($id, ShipmentBuilder::new()->manifested()['trackingNumber']->value));
    }

    #[Test]
    public function itFailsWhenAlreadyTrackedUnderAnotherReference(): void
    {
        // Given
        $shipment = ShipmentBuilder::new()->prepared()->manifested()->create();
        $this->store($shipment);

        // Then
        $this->expectException(ShipmentAlreadyTrackedException::class);

        // When
        $this->dispatch(new ManifestShipment($shipment->id->toString(), ShipmentBuilder::new()->manifested()['trackingNumber']->value));
    }

    #[Test]
    public function itFailsWhenNotPrepared(): void
    {
        // Given
        $shipment = ShipmentBuilder::new()->create();
        $this->store($shipment);

        // Then
        $this->expectException(ShipmentInvalidTransitionException::class);

        // When
        $this->dispatch(new ManifestShipment($shipment->id->toString(), ShipmentBuilder::new()->manifested()['trackingNumber']->value));
    }

    #[Test]
    public function itFailsWhenTrackingNumberAlreadyTaken(): void
    {
        // Given
        $trackingNumber = ShipmentBuilder::new()->manifested()['trackingNumber']->value;
        $this->uniqueValues->reserve(UniqueKey::for(ShipmentUniqueKey::TRACKING_NUMBER), $trackingNumber, ShipmentBuilder::new()->create()->id->toString());
        $shipment = ShipmentBuilder::new()->prepared()->create();
        $this->store($shipment);

        // Then
        $this->expectException(ShipmentTrackingNumberAlreadyTakenException::class);

        // When
        $this->dispatch(new ManifestShipment($shipment->id->toString(), $trackingNumber));
    }
}
