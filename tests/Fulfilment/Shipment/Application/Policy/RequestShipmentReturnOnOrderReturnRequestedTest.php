<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Policy;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Policy\RequestShipmentReturnOnOrderReturnRequested;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\IntegrationEvent\OrderReturnRequested\OrderReturnRequestedIntegrationEvent;
use Support\AbstractIntegrationTestCase;

final class RequestShipmentReturnOnOrderReturnRequestedTest extends AbstractIntegrationTestCase
{
    private RequestShipmentReturnOnOrderReturnRequested $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = $this->service(RequestShipmentReturnOnOrderReturnRequested::class);
    }

    #[Test]
    public function itRequests(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()->withOrderId($orderId)->prepared()->manifested()->dispatched()->delivered()->store();

        // When
        ($this->policy)(new OrderReturnRequestedIntegrationEvent($orderId, '2026-01-10T00:00:00+00:00'));

        // Then
        $results = iterator_to_array($this->service(ShipmentFinderInterface::class), false);
        self::assertCount(1, $results);
        self::assertSame($shipment->id->toString(), $results[0]->id);
        self::assertSame(ShipmentStatus::RETURN_REQUESTED, $results[0]->status);
    }
}
