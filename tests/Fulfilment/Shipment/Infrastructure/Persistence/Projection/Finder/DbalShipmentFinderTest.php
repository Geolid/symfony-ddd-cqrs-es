<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\Persistence\Projection\Finder;

use Fulfilment\Shipment\Application\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
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
    public function itLists(): void
    {
        // Given
        $order = OrderTestFactory::new()->store();
        $shipment = ShipmentTestFactory::new()
            ->withOrderId($order->id->toString())
            ->prepared()
            ->manifested('ACME-4Q7X2K9')
            ->dispatched()
            ->store();

        // When
        $results = iterator_to_array($this->finder);

        // Then
        self::assertCount(1, $results);
        $result = $results[0];
        self::assertInstanceOf(ShipmentResult::class, $result);
        self::assertSame($shipment->id->toString(), $result->id);
        self::assertSame($shipment->orderId, $result->orderId);
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
        self::assertSame('ACME-4Q7X2K9', $result->trackingReference);
        self::assertNotNull($result->dispatchedAt);
        self::assertNull($result->deliveredAt);
    }

    #[Test]
    public function itFiltersByStatus(): void
    {
        // Given
        $manifested = ShipmentTestFactory::new()->prepared()->manifested()->store();
        $dispatched = ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->store();
        ShipmentTestFactory::new()->prepared()->manifested()->dispatched()->delivered()->many(2)->store();

        // When
        $results = iterator_to_array($this->finder->byStatus('manifested'));

        // Then
        self::assertCount(1, $results);
        $result = $results[0];
        self::assertSame($manifested->id->toString(), $result->id);
        self::assertSame($manifested->orderId, $result->orderId);
        self::assertSame(ShipmentStatus::MANIFESTED, $result->status);
        self::assertNotNull($result->trackingReference);
        self::assertNotNull($result->createdAt);
        self::assertNull($result->dispatchedAt);
        self::assertNull($result->deliveredAt);

        // When
        $results = iterator_to_array($this->finder->byStatus('manifested', 'dispatched'));

        // Then
        self::assertCount(2, $results);
        self::assertEqualsCanonicalizing(
            [$manifested->id->toString(), $dispatched->id->toString()],
            array_map(static fn (ShipmentResult $result): string => $result->id, $results),
        );
    }

    #[Test]
    public function itFiltersManifestedBefore(): void
    {
        // Given
        $cutoff = '2026-06-01T00:00:00+00:00';
        $stale = ShipmentTestFactory::new()->prepared()->manifested(manifestedAt: new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))->store();
        ShipmentTestFactory::new()->prepared()->manifested(manifestedAt: new \DateTimeImmutable('2026-06-15T00:00:00+00:00'))->store();
        ShipmentTestFactory::new()->prepared()->store();

        // When
        $results = iterator_to_array($this->finder->manifestedBefore($cutoff));

        // Then
        self::assertCount(1, $results);
        self::assertSame($stale->id->toString(), $results[0]->id);
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
        self::assertSame($shipment->id->toString(), $results[0]->id);
    }

    #[Test]
    public function itGetsByTrackingReference(): void
    {
        // Given
        $tracked = ShipmentTestFactory::new()->prepared()->manifested('ACME-4Q7X2K9')->dispatched()->store();
        $other = ShipmentTestFactory::new()->prepared()->manifested('ACME-OTHER')->dispatched()->store();

        // When
        $result = $this->finder->ofTrackingReference('ACME-4Q7X2K9');

        // Then
        self::assertSame($tracked->id->toString(), $result->id);
        self::assertSame($tracked->orderId, $result->orderId);
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
        self::assertSame('ACME-4Q7X2K9', $result->trackingReference);
        self::assertNotNull($result->dispatchedAt);
        self::assertNull($result->deliveredAt);

        // When
        $otherResult = $this->finder->ofTrackingReference('ACME-OTHER');

        // Then
        self::assertSame($other->id->toString(), $otherResult->id);
    }

    #[Test]
    public function itThrowsOnUnknownTrackingReference(): void
    {
        // Then
        $this->expectException(ShipmentResultNotFoundException::class);

        // When
        $this->finder->ofTrackingReference('ACME-NEVER-ISSUED');
    }

    #[Test]
    public function itGetsByReturnTrackingReference(): void
    {
        // Given
        $tracked = ShipmentTestFactory::new()
            ->prepared()
            ->manifested('ACME-4Q7X2K9')
            ->dispatched()
            ->delivered()
            ->returnRequested()
            ->returnManifested('ACME-RETURN-1')
            ->store();
        $other = ShipmentTestFactory::new()
            ->prepared()
            ->manifested('ACME-OTHER')
            ->dispatched()
            ->delivered()
            ->returnRequested()
            ->returnManifested('ACME-RETURN-OTHER')
            ->store();

        // When
        $result = $this->finder->ofReturnTrackingReference('ACME-RETURN-1');

        // Then
        self::assertSame($tracked->id->toString(), $result->id);
        self::assertSame($tracked->orderId, $result->orderId);
        self::assertSame(ShipmentStatus::RETURN_MANIFESTED, $result->status);
        self::assertSame('ACME-RETURN-1', $result->returnTrackingReference);

        // When
        $otherResult = $this->finder->ofReturnTrackingReference('ACME-RETURN-OTHER');

        // Then
        self::assertSame($other->id->toString(), $otherResult->id);
    }

    #[Test]
    public function itThrowsOnUnknownReturnTrackingReference(): void
    {
        // Then
        $this->expectException(ShipmentResultNotFoundException::class);

        // When
        $this->finder->ofReturnTrackingReference('ACME-RETURN-NEVER-ISSUED');
    }
}
