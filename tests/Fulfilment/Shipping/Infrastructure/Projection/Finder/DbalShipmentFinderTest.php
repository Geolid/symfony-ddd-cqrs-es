<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Infrastructure\Projection\Finder;

use Fulfilment\Shipping\Application\Finder\Shipment\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentResult;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Fulfilment\Shipping\Domain\Shipment;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Fulfilment\Tests\Shipping\Support\Factory\ShipmentFactory;
use Fulfilment\Tests\Shipping\Support\Factory\ShipmentIdFactory;
use Fulfilment\Tests\Shipping\Support\Factory\TrackingNumberFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\ErasureStatus;
use Shared\Tests\Support\TestCase\AbstractIterableFinderTestCase;
use Shared\Tests\Support\TestCase\RealColumnLeadsTrait;
use Symfony\Component\Clock\Clock;

/**
 * @extends AbstractIterableFinderTestCase<ShipmentResult>
 */
final class DbalShipmentFinderTest extends AbstractIterableFinderTestCase
{
    use RealColumnLeadsTrait;

    #[Test]
    public function itGetsById(): void
    {
        // Given
        $other = ShipmentFactory::new()->create();
        $shipment = ShipmentFactory::new()->prepared()->manifested()->dispatched()->create();
        $this->store($other, $shipment);

        // When
        $result = $this->finder()->ofId($shipment->id->toString());

        // Then
        self::assertSame($shipment->id->toString(), $result->id);
        self::assertSame($shipment->orderId, $result->orderId);
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
        self::assertSame($shipment->origin->recipientName, $result->origin->recipientName);
        self::assertSame($shipment->destination->recipientName, $result->destination->recipientName);
        self::assertSame($shipment->customerId, $result->customerId);
        self::assertSame(ErasureStatus::RETAINED, $result->erasureStatus);
    }

    #[Test]
    public function itThrowsWhenIdNotFound(): void
    {
        // Then
        $this->expectException(ShipmentResultNotFoundException::class);

        // When
        $this->finder()->ofId(ShipmentIdFactory::new()->create()->toString());
    }

    #[Test]
    public function itGetsByTrackingNumber(): void
    {
        // Given
        $other = ShipmentFactory::new()->prepared()->manifested()->dispatched()->create();
        $trackingNumber = TrackingNumberFactory::new()->create();
        $tracked = ShipmentFactory::new()->prepared()->manifested($trackingNumber)->dispatched()->create();
        $this->store($other, $tracked);

        // When
        $result = $this->finder()->ofTrackingNumber($trackingNumber->value);

        // Then
        self::assertSame($tracked->id->toString(), $result->id);
        self::assertSame($tracked->orderId, $result->orderId);
        self::assertSame(ShipmentStatus::DISPATCHED, $result->status);
        self::assertSame($trackingNumber->value, $result->trackingNumber);
        self::assertNotNull($result->dispatchedAt);
        self::assertNull($result->deliveredAt);
    }

    #[Test]
    public function itThrowsWhenTrackingNumberNotFound(): void
    {
        // Then
        $this->expectException(ShipmentResultNotFoundException::class);

        // When
        $this->finder()->ofTrackingNumber(TrackingNumberFactory::new()->create()->value);
    }

    #[Test]
    public function itFindsByOrder(): void
    {
        // Given
        $other = ShipmentFactory::new()->create();
        $shipment = ShipmentFactory::new()->create();
        $this->store($other, $shipment);

        // When
        $found = $this->finder()->ofOrderOrNull($shipment->orderId);
        $notFound = $this->finder()->ofOrderOrNull(Uuid::uuid7()->toString());

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
        $other = ShipmentFactory::new()->create();
        $shipment = ShipmentFactory::new()->withCustomerId($customerId)->create();
        $this->store($other, $shipment);

        // When
        $results = iterator_to_array($this->finder()->byCustomer($customerId));

        // Then
        self::assertCount(1, $results);
        self::assertSame($shipment->id->toString(), $results[0]->id);
    }

    #[Test]
    public function itFiltersStalledBefore(): void
    {
        // Given
        $now = Clock::get()->now();
        $freshManifested = ShipmentFactory::new()->prepared()->manifested(manifestedAt: $now->modify('+1 day'))->create();
        $notManifested = ShipmentFactory::new()->prepared()->create();
        $staleManifested = ShipmentFactory::new()->prepared()->manifested(manifestedAt: $now->modify('-1 day'))->create();
        $staleDispatched = ShipmentFactory::new()
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
        $shipments = ShipmentFactory::new()->many($count)->create();
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
    protected function seedConflictingOrder(): array
    {
        $orderIdByShipmentId = [];
        foreach ([Uuid::uuid7()->toString(), Uuid::uuid7()->toString()] as $orderId) {
            $orderIdByShipmentId[ShipmentId::forOrder($orderId)->toString()] = $orderId;
        }
        ksort($orderIdByShipmentId);
        [$smallerId, $largerId] = array_keys($orderIdByShipmentId);

        $now = Clock::get()->now();
        $first = ShipmentFactory::new()->withOrderId($orderIdByShipmentId[$largerId])->withCreatedAt($now)->create();
        $second = ShipmentFactory::new()->withOrderId($orderIdByShipmentId[$smallerId])->withCreatedAt($now->modify('+1 hour'))->create();
        $this->store($first, $second);

        return [$largerId, $smallerId];
    }
}
