<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\IntegrationEvent\ShipmentReturnRejected;

use Fulfilment\Shipment\Application\IntegrationEvent\ShipmentReturnRejected\ShipmentReturnRejectedIntegrationEvent;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

final class ShipmentReturnRejectedPublisherTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itPublishes(): void
    {
        // Given
        $builder = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->delivered()->returnRequested()->returnManifested()->returnDispatched()->returnReceived()->returnRejected();
        $shipment = $builder->create();

        // When
        $this->store($shipment);

        // Then
        $event = $this->publishedEventOf(ShipmentReturnRejectedIntegrationEvent::class);
        self::assertSame($shipment->id->toString(), $event->shipmentId);
        self::assertSame($builder['orderId'], $event->orderId);
        self::assertSame($builder['returnRejectionReason'], $event->reason);
        self::assertSame(
            $builder['returnRejectedAt']->format(\DateTimeInterface::ATOM),
            $event->rejectedAt->format(\DateTimeInterface::ATOM),
        );
    }
}
