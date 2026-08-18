<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Processor;

use Fulfilment\Shipment\Application\Event\ShipmentReturnApprovedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Processor\ConfirmOrderReturnOnShipmentReturnApproved;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class ConfirmOrderReturnOnShipmentReturnApprovedTest extends AbstractIntegrationTestCase
{
    private ConfirmOrderReturnOnShipmentReturnApproved $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(ConfirmOrderReturnOnShipmentReturnApproved::class);
    }

    #[Test]
    public function itConfirmsOnShipmentReturnApproved(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->dispatched()->delivered()->returnRequested()->store();

        // When
        ($this->processor)(new ShipmentReturnApprovedIntegrationEvent(Uuid::uuid7()->toString(), $order->id->toString(), '2026-01-11T00:00:00+00:00'));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id->toString());
        self::assertSame(OrderStatus::RETURNED, $result->status);
    }
}
