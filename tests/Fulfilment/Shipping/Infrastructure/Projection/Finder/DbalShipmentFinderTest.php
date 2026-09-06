<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Infrastructure\Projection\Finder;

use Fulfilment\Shipping\Application\Finder\Shipment\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentResult;
use Fulfilment\Shipping\Application\ShipmentDirection;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Fulfilment\Shipping\Domain\Shipment;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
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
        $builder = ShipmentBuilder::new()->prepared()->manifested()->dispatched();
        $shipment = $builder->create();
        $this->store($other, $shipment);

        // When
        $result = $this->finder->ofId($shipment->id->toString());

        // Then
        self::assertSame($shipment->id->toString(), $result->id);
        self::assertSame($builder['reference'], $result->reference);
        self::assertSame(ShipmentDirection::OUTBOUND, $result->direction);
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
        self::assertSame($builder['origin']->recipientName, $result->origin->recipientName);
        self::assertSame($builder['destination']->recipientName, $result->destination->recipientName);
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
    public function itGetsByTrackingNumber(): void
    {
        // Given
        $other = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->create();
        $builder = ShipmentBuilder::new()->prepared()->manifested()->dispatched();
        $tracked = $builder->create();
        $this->store($other, $tracked);

        // When
        $result = $this->finder->ofTrackingNumber($builder['trackingNumber']->value);

        // Then
        self::assertSame($tracked->id->toString(), $result->id);
        self::assertSame($builder['reference'], $result->reference);
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
        self::assertSame($builder['trackingNumber']->value, $result->trackingNumber);
        self::assertNotNull($result->dispatchedAt);
        self::assertNull($result->deliveredAt);
    }

    #[Test]
    public function itThrowsWhenTrackingNumberNotFound(): void
    {
        // Then
        $this->expectException(ShipmentResultNotFoundException::class);

        // When
        $this->finder->ofTrackingNumber(ShipmentBuilder::sample('trackingNumber')->value);
    }

    #[Test]
    public function itFindsByReference(): void
    {
        // Given
        $other = ShipmentBuilder::new()->create();
        $builder = ShipmentBuilder::new();
        $shipment = $builder->create();
        $this->store($other, $shipment);

        // When
        $found = $this->finder->ofReferenceOrNull($builder['reference']);
        $notFound = $this->finder->ofReferenceOrNull(ShipmentBuilder::sample('reference'));

        // Then
        self::assertNotNull($found);
        self::assertSame($shipment->id->toString(), $found->id);
        self::assertNull($notFound);
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
        $this->store($freshManifested, $notManifested, $staleManifested, $staleDispatched);

        // When
        $results = iterator_to_array($this->finder->stalledBefore($now));

        // Then
        self::assertCount(2, $results);
        self::assertEqualsCanonicalizing(
            [$staleManifested->id->toString(), $staleDispatched->id->toString()],
            array_map(static fn (ShipmentResult $result): string => $result->id, $results),
        );
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
        self::assertSame($this->ids(...$shipments), $this->resultIds($results));
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
    private function ids(Shipment ...$shipments): array
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
