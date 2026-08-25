<?php

declare(strict_types=1);

namespace Sales\Tests\Order\Application\Policy;

use Fulfilment\Shipment\Application\IntegrationEvent\ShipmentDispatched\ShipmentDispatchedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Policy\DispatchOrderOnShipmentDispatched;
use Sales\Order\Application\Status\OrderStatus;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class DispatchOrderOnShipmentDispatchedTest extends AbstractIntegrationTestCase
{
    private DispatchOrderOnShipmentDispatched $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = $this->service(DispatchOrderOnShipmentDispatched::class);
    }

    #[Test]
    public function itDispatches(): void
    {
        // Given
        $order = OrderTestFactory::new()->confirmed()->store();

        // When
        ($this->policy)(new ShipmentDispatchedIntegrationEvent(Uuid::uuid7()->toString(), $order->id->toString(), '2026-01-02T00:00:00+00:00'));

        // Then
        $result = $this->service(OrderFinderInterface::class)->ofId($order->id->toString());
        self::assertSame(OrderStatus::DISPATCHED, $result->status);
    }
}
