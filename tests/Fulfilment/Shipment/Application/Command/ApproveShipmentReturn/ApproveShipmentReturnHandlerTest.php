<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\ApproveShipmentReturn;

use Fulfilment\Shipment\Application\Command\ApproveShipmentReturn\ApproveShipmentReturn;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class ApproveShipmentReturnHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itApprovesReturnWhenReceived(): void
    {
        // Given
        $returnTrackingReference = 'ACME-RETURN-1';
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested($returnTrackingReference)->returnDispatched()->returnReceived()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new ApproveShipmentReturn($shipment->id->toString()));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofReturnTrackingReference($returnTrackingReference);
        self::assertSame(ShipmentStatus::RETURN_APPROVED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenAlreadyApproved(): void
    {
        // Given
        $returnTrackingReference = 'ACME-RETURN-1';
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested($returnTrackingReference)->returnDispatched()->returnReceived()->returnApproved()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new ApproveShipmentReturn($shipment->id->toString()));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofReturnTrackingReference($returnTrackingReference);
        self::assertSame(ShipmentStatus::RETURN_APPROVED, $result->status);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Given
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString())->toString();

        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->dispatch(new ApproveShipmentReturn($id));
    }

    #[Test]
    public function itFailsWhenNotReceived(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->create();
        $this->store($shipment);

        // Then
        $this->expectException(ShipmentInvalidTransitionException::class);

        // When
        $this->dispatch(new ApproveShipmentReturn($shipment->id->toString()));
    }
}
