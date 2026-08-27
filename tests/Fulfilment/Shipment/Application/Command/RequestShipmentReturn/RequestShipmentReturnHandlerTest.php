<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\RequestShipmentReturn;

use Fulfilment\Shipment\Application\Command\RequestShipmentReturn\RequestShipmentReturn;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class RequestShipmentReturnHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itRequestsReturnWhenDelivered(): void
    {
        // Given
        $trackingReference = 'ACME-4Q7X2K9';
        $shipment = ShipmentTestFactory::new()->prepared()->manifested($trackingReference)->dispatched()->delivered()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new RequestShipmentReturn($shipment->id->toString()));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofTrackingReference($trackingReference);
        self::assertSame(ShipmentStatus::RETURN_REQUESTED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenNotDelivered(): void
    {
        // Given
        $trackingReference = 'ACME-4Q7X2K9';
        $shipment = ShipmentTestFactory::new()->prepared()->manifested($trackingReference)->dispatched()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new RequestShipmentReturn($shipment->id->toString()));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofTrackingReference($trackingReference);
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();

        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->dispatch(new RequestShipmentReturn($id));
    }
}
