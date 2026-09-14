<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Infrastructure\Projection\Finder;

use Fulfilment\Shipping\Application\Finder\Shipment\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentResult;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Fulfilment\Shipping\Domain\Shipment;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\ErasureStatus;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;
use Shared\Tests\Support\TestCase\IdTiebreakerTrait;
use Symfony\Component\Clock\Clock;

/**
 * @extends AbstractIterableFinderTestCase<ShipmentResult>
 */
final class DbalShipmentFinderTest extends AbstractIterableFinderTestCase
{
    use IdTiebreakerTrait;

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = ShipmentBuilder::new()->create();
        $builder = ShipmentBuilder::new()->prepared()->manifested()->dispatched();
        $shipment = $builder->create();
        $this->store($other, $shipment);

        // When
        $result = $this->finder()->ofId($shipment->id->toString());

        // Then
        self::assertSame($shipment->id->toString(), $result->id);
        self::assertSame($builder['orderId'], $result->orderId);
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
        self::assertSame($builder['origin']->recipientName, $result->origin->recipientName);
        self::assertSame($builder['destination']->recipientName, $result->destination->recipientName);
        self::assertSame($builder['customerId'], $result->customerId);
        self::assertSame(ErasureStatus::RETAINED, $result->erasureStatus);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(ShipmentResultNotFoundException::class);

        // When
        $this->finder()->ofId(Uuid::uuid7()->toString());
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
        $result = $this->finder()->ofTrackingNumber($builder['trackingNumber']->value);

        // Then
        self::assertSame($tracked->id->toString(), $result->id);
        self::assertSame($builder['orderId'], $result->orderId);
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
        $this->finder()->ofTrackingNumber(ShipmentBuilder::sample('trackingNumber')->value);
    }

    #[Test]
    public function itFindsByOrder(): void
    {
        // Given
        $other = ShipmentBuilder::new()->create();
        $builder = ShipmentBuilder::new();
        $shipment = $builder->create();
        $this->store($other, $shipment);

        // When
        $found = $this->finder()->ofOrderOrNull($builder['orderId']);
        $notFound = $this->finder()->ofOrderOrNull(ShipmentBuilder::sample('orderId'));

        // Then
        self::assertNotNull($found);
        self::assertSame($shipment->id->toString(), $found->id);
        self::assertNull($notFound);
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
        $results = iterator_to_array($this->finder()->byCustomer($customerId));

        // Then
        self::assertCount(1, $results);
        self::assertSame($shipment->id->toString(), $results[0]->id);
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
        $results = iterator_to_array($this->finder()->byStatus(ShipmentStatus::MANIFESTED, ShipmentStatus::DISPATCHED));

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
        $results = iterator_to_array($this->finder()->stalledBefore($now));

        // Then
        self::assertCount(2, $results);
        self::assertEqualsCanonicalizing(
            [$staleManifested->id->toString(), $staleDispatched->id->toString()],
            array_map(static fn (ShipmentResult $result): string => $result->id, $results),
        );
    }

    protected function finder(): ShipmentFinderInterface
    {
        return $this->service(ShipmentFinderInterface::class);
    }

    /**
     * @return list<string>
     */
    protected function seed(int $count): array
    {
        $shipments = ShipmentBuilder::new()->many($count)->create();
        $this->store(...$shipments);

        return array_map(static fn (Shipment $shipment): string => $shipment->id->toString(), $shipments);
    }

    protected function indexOf(object $result): string
    {
        return $result->id;
    }

    /**
     * @return array{string, string}
     */
    protected function seedTie(): array
    {
        $orderIdByShipmentId = [];
        foreach ([Uuid::uuid7()->toString(), Uuid::uuid7()->toString()] as $orderId) {
            $orderIdByShipmentId[ShipmentId::forOrder($orderId)->toString()] = $orderId;
        }
        ksort($orderIdByShipmentId);
        [$firstId, $secondId] = array_keys($orderIdByShipmentId);

        $tiedAt = Clock::get()->now();
        $second = ShipmentBuilder::new()->withOrderId($orderIdByShipmentId[$secondId])->withCreatedAt($tiedAt)->create();
        $first = ShipmentBuilder::new()->withOrderId($orderIdByShipmentId[$firstId])->withCreatedAt($tiedAt)->create();
        $this->store($second, $first);

        return [$firstId, $secondId];
    }

    /**
     * @return array{string, string}
     */
    protected function seedConflictingOrder(): array
    {
        $orderIdByShipmentId = [];
        foreach ([Uuid::uuid7()->toString(), Uuid::uuid7()->toString()] as $orderId) {
            $orderIdByShipmentId[ShipmentId::forOrder($orderId)->toString()] = $orderId;
        }
        ksort($orderIdByShipmentId);
        [$smallerId, $largerId] = array_keys($orderIdByShipmentId);

        $now = Clock::get()->now();
        $first = ShipmentBuilder::new()->withOrderId($orderIdByShipmentId[$largerId])->withCreatedAt($now)->create();
        $second = ShipmentBuilder::new()->withOrderId($orderIdByShipmentId[$smallerId])->withCreatedAt($now->modify('+1 hour'))->create();
        $this->store($first, $second);

        return [$largerId, $smallerId];
    }
}
