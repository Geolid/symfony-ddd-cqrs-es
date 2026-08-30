<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Policy;

use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Policy\CancelShipmentOnOrderCancelled;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\IntegrationEvent\OrderCancelled\OrderCancelledIntegrationEvent;
use Support\AbstractIntegrationTestCase;

final class CancelShipmentOnOrderCancelledTest extends AbstractIntegrationTestCase
{
    private CancelShipmentOnOrderCancelled $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = $this->service(CancelShipmentOnOrderCancelled::class);
    }

    #[Test]
    public function itCancels(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()->withOrderId($orderId)->create();
        $this->store($shipment);

        // When
        ($this->policy)(new OrderCancelledIntegrationEvent($orderId, new \DateTimeImmutable('2026-01-02T00:00:00+00:00')));

        // Then
        $results = iterator_to_array($this->service(ShipmentFinderInterface::class)->byCustomer($shipment->customerId), false);
        self::assertCount(1, $results);
        self::assertSame(ShipmentStatus::CANCELLED, $results[0]->status);
    }

    #[Test]
    public function itIgnoresWhenNoneExistForOrder(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();

        // When
        ($this->policy)(new OrderCancelledIntegrationEvent($orderId, new \DateTimeImmutable('2026-01-02T00:00:00+00:00')));

        // Then
        self::expectNotToPerformAssertions();
    }
}
