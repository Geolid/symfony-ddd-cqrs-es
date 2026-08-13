<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\AssignTrackingReference;

use Fulfilment\Shipment\Application\Command\AssignTrackingReference\AssignTrackingReference;
use Fulfilment\Shipment\Application\Exception\TrackingReferenceAlreadyTakenException;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentUniqueValue;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\Service\UniqueValueRegistryInterface;
use Support\AbstractIntegrationTestCase;

final class AssignTrackingReferenceHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itTracksADispatchedShipment(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->dispatched()->store();

        // When
        $this->dispatch(new AssignTrackingReference($shipment->id()->toString(), 'ACME-4Q7X2K9'));

        // Then
        $results = iterator_to_array($this->service(ShipmentFinderInterface::class), false);
        self::assertCount(1, $results);
        self::assertSame('ACME-4Q7X2K9', $results[0]->trackingReference);
    }

    #[Test]
    public function itFailsWhenTheShipmentHasNotLeftYet(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->store();

        // Then
        $this->expectException(ShipmentInvalidTransitionException::class);

        // When
        $this->dispatch(new AssignTrackingReference($shipment->id()->toString(), 'ACME-4Q7X2K9'));
    }

    #[Test]
    public function itFailsWhenTheShipmentDoesNotExist(): void
    {
        // Given
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();

        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->dispatch(new AssignTrackingReference($id, 'ACME-4Q7X2K9'));
    }

    #[Test]
    public function itFailsWhenTheTrackingReferenceIsAlreadyTaken(): void
    {
        // Given
        $trackingReference = 'ACME-4Q7X2K9';
        $this->service(UniqueValueRegistryInterface::class)->reserve(ShipmentUniqueValue::TRACKING_REFERENCE, $trackingReference);
        $shipment = ShipmentTestFactory::new()->dispatched()->store();

        // Then
        $this->expectException(TrackingReferenceAlreadyTakenException::class);

        // When
        $this->dispatch(new AssignTrackingReference($shipment->id()->toString(), $trackingReference));
    }
}
