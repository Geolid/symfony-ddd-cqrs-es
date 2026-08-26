<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use Fulfilment\Shipment\Application\IntegrationEvent\ShipmentReturnApproved\ShipmentReturnApprovedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Policy\ConfirmOrderReturnOnShipmentReturnApproved;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class ConfirmOrderReturnOnShipmentReturnApprovedTest extends AbstractIntegrationTestCase
{
    private ConfirmOrderReturnOnShipmentReturnApproved $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = $this->service(ConfirmOrderReturnOnShipmentReturnApproved::class);
    }

    #[Test]
    public function itConfirms(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->dispatched()->delivered()->returnRequested()->create();
        $this->store($order);

        // When
        ($this->policy)(new ShipmentReturnApprovedIntegrationEvent(Uuid::uuid7()->toString(), $order->id->toString(), '2026-01-11T00:00:00+00:00'));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id->toString());
        self::assertSame(OrderStatus::RETURNED, $result->status);
    }
}
