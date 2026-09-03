<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\Projection\Finder;

use Fulfilment\Shipment\Application\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Shipment\Domain\Shipment;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class DbalShipmentFinderTest extends AbstractIntegrationTestCase
{
    private ShipmentFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(ShipmentFinderInterface::class);
    }

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = ShipmentBuilder::new()->create();
        $shipment = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->create();
        $this->store($other, $shipment);

        // When
        $result = $this->finder->ofId($shipment->id->toString());

        // Then
        self::assertSame($shipment->id->toString(), $result->id);
        self::assertSame($shipment->orderId, $result->orderId);
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(ShipmentResultNotFoundException::class);

        // When
        $this->finder->ofId(Uuid::uuid7()->toString());
    }

    #[Test]
    public function itGetsByTrackingReference(): void
    {
        // Given
        $other = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->create();
        $trackedFactory = ShipmentBuilder::new()->prepared()->manifested()->dispatched();
        $tracked = $trackedFactory->create();
        $this->store($other, $tracked);

        // When
        $result = $this->finder->ofTrackingReference($trackedFactory['trackingReference']->value);

        // Then
        self::assertSame($tracked->id->toString(), $result->id);
        self::assertSame($tracked->orderId, $result->orderId);
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
        self::assertSame($trackedFactory['trackingReference']->value, $result->trackingReference);
        self::assertNotNull($result->dispatchedAt);
        self::assertNull($result->deliveredAt);
    }

    #[Test]
    public function itThrowsWhenTrackingReferenceNotFound(): void
    {
        // Then
        $this->expectException(ShipmentResultNotFoundException::class);

        // When
        $this->finder->ofTrackingReference(ShipmentBuilder::sample('trackingReference')->value);
    }

    #[Test]
    public function itGetsByReturnTrackingReference(): void
    {
        // Given
        $other = ShipmentBuilder::new()
            ->prepared()
            ->manifested()
            ->dispatched()
            ->delivered()
            ->returnRequested()
            ->returnManifested()
            ->create();
        $trackedFactory = ShipmentBuilder::new()
            ->prepared()
            ->manifested()
            ->dispatched()
            ->delivered()
            ->returnRequested()
            ->returnManifested();
        $tracked = $trackedFactory->create();
        $this->store($other, $tracked);

        // When
        $result = $this->finder->ofReturnTrackingReference($trackedFactory['returnTrackingReference']->value);

        // Then
        self::assertSame($tracked->id->toString(), $result->id);
        self::assertSame($tracked->orderId, $result->orderId);
        self::assertSame(ShipmentStatus::RETURN_MANIFESTED, $result->status);
        self::assertSame($trackedFactory['returnTrackingReference']->value, $result->returnTrackingReference);
    }

    #[Test]
    public function itThrowsWhenReturnTrackingReferenceNotFound(): void
    {
        // Then
        $this->expectException(ShipmentResultNotFoundException::class);

        // When
        $this->finder->ofReturnTrackingReference(ShipmentBuilder::sample('returnTrackingReference')->value);
    }

    #[Test]
    public function itFiltersByStatus(): void
    {
        // Given
        $other = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->delivered()->create();
        $manifested = ShipmentBuilder::new()->prepared()->manifested()->create();
        $dispatched = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->create();
        $this->store($other, $manifested, $dispatched);

        // When
        $results = iterator_to_array($this->finder->byStatus(ShipmentStatus::MANIFESTED, ShipmentStatus::DISPATCHED));

        // Then
        self::assertCount(2, $results);
        self::assertEqualsCanonicalizing(
            [$manifested->id->toString(), $dispatched->id->toString()],
            array_map(static fn (ShipmentResult $result): string => $result->id, $results),
        );
    }

    #[Test]
    public function itFiltersStalledBefore(): void
    {
        // Given
        $now = Clock::get()->now();
        $freshManifested = ShipmentBuilder::new()->prepared()->manifested(manifestedAt: $now->modify('+1 day'))->create();
        $notManifested = ShipmentBuilder::new()->prepared()->create();
        $staleManifested = ShipmentBuilder::new()->prepared()->manifested(manifestedAt: $now->modify('-1 day'))->create();
        $staleDispatched = ShipmentBuilder::new()
            ->prepared()
            ->manifested(manifestedAt: $now->modify('-2 days'))
            ->dispatched($now->modify('-1 day'))
            ->create();
        $staleReturnManifested = ShipmentBuilder::new()
            ->prepared()
            ->manifested(manifestedAt: $now->modify('-5 days'))
            ->dispatched($now->modify('-4 days'))
            ->delivered($now->modify('-3 days'))
            ->returnRequested($now->modify('-2 days'))
            ->returnManifested(manifestedAt: $now->modify('-1 day'))
            ->create();
        $staleReturnDispatched = ShipmentBuilder::new()
            ->prepared()
            ->manifested(manifestedAt: $now->modify('-6 days'))
            ->dispatched($now->modify('-5 days'))
            ->delivered($now->modify('-4 days'))
            ->returnRequested($now->modify('-3 days'))
            ->returnManifested(manifestedAt: $now->modify('-2 days'))
            ->returnDispatched($now->modify('-1 day'))
            ->create();
        $this->store($freshManifested, $notManifested, $staleManifested, $staleDispatched, $staleReturnManifested, $staleReturnDispatched);

        // When
        $results = iterator_to_array($this->finder->stalledBefore($now));

        // Then
        self::assertCount(4, $results);
        self::assertEqualsCanonicalizing(
            [$staleManifested->id->toString(), $staleDispatched->id->toString(), $staleReturnManifested->id->toString(), $staleReturnDispatched->id->toString()],
            array_map(static fn (ShipmentResult $result): string => $result->id, $results),
        );
    }

    #[Test]
    public function itFiltersByCustomer(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $other = ShipmentBuilder::new()->create();
        $shipment = ShipmentBuilder::new()->withCustomerId($customerId)->create();
        $this->store($other, $shipment);

        // When
        $results = iterator_to_array($this->finder->byCustomer($customerId));

        // Then
        self::assertCount(1, $results);
        self::assertSame($shipment->id->toString(), $results[0]->id);
    }

    #[Test]
    public function itLists(): void
    {
        // Given
        $shipments = ShipmentBuilder::new()->many(5)->create();
        $this->store(...$shipments);

        // When
        $results = iterator_to_array($this->finder);

        // Then
        self::assertSame($this->shipmentIds(...$shipments), $this->resultIds($results));
    }

    #[Test]
    public function itListsWhenEmpty(): void
    {
        // When
        $results = iterator_to_array($this->finder);

        // Then
        self::assertEmpty($results);
    }

    /**
     * @return list<string>
     */
    private function shipmentIds(Shipment ...$shipments): array
    {
        $ids = [];
        foreach ($shipments as $shipment) {
            $ids[] = $shipment->id->toString();
        }

        return $ids;
    }

    /**
     * @param iterable<ShipmentResult> $results
     *
     * @return list<string>
     */
    private function resultIds(iterable $results): array
    {
        $ids = [];
        foreach ($results as $result) {
            $ids[] = $result->id;
        }

        return $ids;
    }
}
