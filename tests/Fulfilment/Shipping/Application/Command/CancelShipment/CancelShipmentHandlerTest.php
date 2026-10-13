<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Command\CancelShipment;

use Fulfilment\Shipping\Application\Command\CancelShipment\CancelShipment;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Fulfilment\Tests\Shipping\Support\Factory\ShipmentFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class CancelShipmentHandlerTest extends AbstractIntegrationTestCase
{
    private ShipmentFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(ShipmentFinderInterface::class);
    }

    #[Test]
    public function itCancelsWhenPending(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentFactory::new()->withOrderId($orderId)->create();
        $this->store($shipment);

        // When
        $this->dispatch(new CancelShipment($orderId));

        // Then
        $result = $this->finder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::CANCELLED, $result->status);
        self::assertNotNull($result->cancelledAt);
    }

    #[Test]
    public function itIgnoresWhenAlreadyDelivered(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $shipment = ShipmentFactory::new()->withOrderId($orderId)->prepared()->manifested()->dispatched()->delivered()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new CancelShipment($orderId));

        // Then
        $result = $this->finder->ofId($shipment->id->toString());
        self::assertSame(ShipmentStatus::DELIVERED, $result->status);
    }

    #[Test]
    public function itIgnoresWhenNotFound(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();

        // When
        $this->dispatch(new CancelShipment($orderId));

        // Then
        $result = $this->finder->ofOrderOrNull($orderId);
        self::assertNull($result);
    }
}
