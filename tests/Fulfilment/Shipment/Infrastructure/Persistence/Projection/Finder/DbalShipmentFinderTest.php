<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\Persistence\Projection\Finder;

use Fulfilment\Shipment\Application\Enum\ShipmentStatus;
use Fulfilment\Shipment\Application\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Support\AbstractIntegrationTestCase;

final class DbalShipmentFinderTest extends AbstractIntegrationTestCase
{
    private ShipmentFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(ShipmentFinderInterface::class);
    }

    #[Test]
    public function itListsShipments(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();
        $shipment = ShipmentTestFactory::new()
            ->withOrderId($order->id()->toString())
            ->manifested()
            ->dispatched()
            ->tracked('ACME-4Q7X2K9')
            ->store();

        // When
        $results = iterator_to_array($this->finder);

        // Then
        self::assertCount(1, $results);
        $result = $results[0];
        self::assertInstanceOf(ShipmentResult::class, $result);
        self::assertSame($shipment->id()->toString(), $result->id);
        self::assertSame($shipment->orderId(), $result->orderId);
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
        self::assertSame('ACME-4Q7X2K9', $result->trackingReference);
        self::assertNotNull($result->dispatchedAt);
        self::assertNull($result->deliveredAt);
    }

    #[Test]
    public function itFiltersByStatus(): void
    {
        // Given
        $pending = ShipmentTestFactory::new()->store();
        $dispatched = ShipmentTestFactory::new()->manifested()->dispatched()->store();
        ShipmentTestFactory::new()->manifested()->dispatched()->delivered()->many(2)->store();

        // When
        $results = iterator_to_array($this->finder->byStatus('pending'));

        // Then
        self::assertCount(1, $results);
        $result = $results[0];
        self::assertSame($pending->id()->toString(), $result->id);
        self::assertSame($pending->orderId(), $result->orderId);
        self::assertSame(ShipmentStatus::PENDING, $result->status);
        self::assertNull($result->trackingReference);
        self::assertNotNull($result->createdAt);
        self::assertNull($result->dispatchedAt);
        self::assertNull($result->deliveredAt);

        // When
        $results = iterator_to_array($this->finder->byStatus('pending', 'dispatched'));

        // Then
        self::assertCount(2, $results);
        self::assertEqualsCanonicalizing(
            [$pending->id()->toString(), $dispatched->id()->toString()],
            array_map(static fn (ShipmentResult $result) => $result->id, $results),
        );
    }

    #[Test]
    public function itFiltersByCustomer(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $shipment = ShipmentTestFactory::new()->withCustomerId($customerId)->store();
        ShipmentTestFactory::new()->store();

        // When
        $results = iterator_to_array($this->finder->byCustomer($customerId));

        // Then
        self::assertCount(1, $results);
        self::assertSame($shipment->id()->toString(), $results[0]->id);
    }

    #[Test]
    public function itGetsByTrackingReference(): void
    {
        // Given
        $tracked = ShipmentTestFactory::new()->manifested()->dispatched()->tracked('ACME-4Q7X2K9')->store();
        ShipmentTestFactory::new()->manifested()->dispatched()->tracked('ACME-OTHER')->store();

        // When
        $result = $this->finder->ofTrackingReference('ACME-4Q7X2K9');

        // Then
        self::assertSame($tracked->id()->toString(), $result->id);
        self::assertSame($tracked->orderId(), $result->orderId);
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
        self::assertSame('ACME-4Q7X2K9', $result->trackingReference);
        self::assertNotNull($result->dispatchedAt);
        self::assertNull($result->deliveredAt);
    }

    #[Test]
    public function itThrowsOnAnUnknownTrackingReference(): void
    {
        // Then
        $this->expectException(ShipmentResultNotFoundException::class);

        // When
        $this->finder->ofTrackingReference('ACME-NEVER-ISSUED');
    }
}
