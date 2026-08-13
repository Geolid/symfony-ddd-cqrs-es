<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\CancelShipmentsForCustomer;

use Fulfilment\Shipment\Application\Command\CancelShipmentsForCustomer\CancelShipmentsForCustomer;
use Fulfilment\Shipment\Application\Enum\ShipmentStatus;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class CancelShipmentsForCustomerHandlerTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itCancelsEveryActiveShipmentOfTheCustomer(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $pending = ShipmentTestFactory::new()->withCustomerId($customerId)->store();
        $dispatched = ShipmentTestFactory::new()->withCustomerId($customerId)->dispatched()->store();
        $delivered = ShipmentTestFactory::new()->withCustomerId($customerId)->dispatched()->delivered()->store();
        $other = ShipmentTestFactory::new()->store();

        // When
        $this->dispatch(new CancelShipmentsForCustomer($customerId));

        // Then
        $finder = $this->service(ShipmentFinderInterface::class);
        $statusesById = [];
        foreach ($finder->byCustomer($customerId) as $result) {
            $statusesById[$result->id] = $result->status;
        }
        self::assertSame(ShipmentStatus::CANCELLED, $statusesById[$pending->id()->toString()]);
        self::assertSame(ShipmentStatus::CANCELLED, $statusesById[$dispatched->id()->toString()]);
        self::assertSame(ShipmentStatus::DELIVERED, $statusesById[$delivered->id()->toString()]);

        $otherResults = array_values(iterator_to_array($finder->byCustomer($other->customerId())));
        self::assertSame(ShipmentStatus::PENDING, $otherResults[0]->status);
    }

    #[Test]
    public function itIgnoresACustomerWithNoShipments(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();

        // When
        $this->dispatch(new CancelShipmentsForCustomer($customerId));

        // Then
        self::expectNotToPerformAssertions();
    }
}
