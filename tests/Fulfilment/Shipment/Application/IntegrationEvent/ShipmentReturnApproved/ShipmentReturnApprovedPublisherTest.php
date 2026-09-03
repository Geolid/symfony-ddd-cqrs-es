<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\IntegrationEvent\ShipmentReturnApproved;

use Fulfilment\Shipment\Application\IntegrationEvent\ShipmentReturnApproved\ShipmentReturnApprovedIntegrationEvent;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class ShipmentReturnApprovedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()->returnApproved();
        $shipment = $builder->create();

        // When
        $this->store($shipment);

        // Then
        $event = $this->publishedEventOf(ShipmentReturnApprovedIntegrationEvent::class);
        self::assertSame($shipment->id->toString(), $event->shipmentId);
        self::assertSame($builder['orderId'], $event->orderId);
        self::assertSame(
            $builder['returnApprovedAt']->format(\DateTimeInterface::ATOM),
            $event->approvedAt->format(\DateTimeInterface::ATOM),
        );
    }
}
