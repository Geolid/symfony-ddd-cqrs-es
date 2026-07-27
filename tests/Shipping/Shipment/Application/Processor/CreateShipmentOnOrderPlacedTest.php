<?php

declare(strict_types=1);

namespace Shipping\Tests\Shipment\Application\Processor;

use Ordering\Order\Application\Event\OrderPlacedIntegrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shipping\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Shipping\Shipment\Application\Processor\CreateShipmentOnOrderPlaced;
use Support\AbstractIntegrationTestCase;

/**
 * Exercises the Processor directly, feeding it the Integration Event it reacts to in
 * production — the same test shape used for any BC-to-BC reaction, without needing a running
 * event-sourcing subscription worker in the test suite (see run_after_aggregate_save in
 * config/packages/patchlevel_event_sourcing.php, which only replays Translators/Projectors
 * synchronously, not Processors).
 */
final class CreateShipmentOnOrderPlacedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itOpensAShipmentForTheNewOrder(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $event = new OrderPlacedIntegrationEvent(
            orderId: $orderId,
            customerId: 'customer-1',
            totalAmountInCents: 4_200,
            placedAt: (new \DateTimeImmutable('2026-01-01T00:00:00+00:00'))->format('c'),
        );

        // When
        ($this->service(CreateShipmentOnOrderPlaced::class))($event);

        // Then
        $results = array_values(iterator_to_array($this->service(ShipmentFinderInterface::class)));
        self::assertCount(1, $results);
        self::assertSame($orderId, $results[0]->orderId);
        self::assertSame('pending', $results[0]->status);
    }
}
