<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Processor;

use Fulfilment\Shipment\Application\Enum\ShipmentStatus;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Processor\CancelShipmentsOnCustomerErased;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Customer\Application\Event\CustomerErasedIntegrationEvent;
use Support\AbstractIntegrationTestCase;

final class CancelShipmentsOnCustomerErasedTest extends AbstractIntegrationTestCase
{
    private const string ERASED_AT = '2026-01-02T00:00:00+00:00';

    private CancelShipmentsOnCustomerErased $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = $this->service(CancelShipmentsOnCustomerErased::class);
    }

    #[Test]
    public function itCancelsEveryActiveShipmentForTheCustomerOnCustomerErased(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();
        $pending = ShipmentTestFactory::new()->withCustomerId($customerId)->store();
        $delivered = ShipmentTestFactory::new()->withCustomerId($customerId)->dispatched()->delivered()->store();
        $other = ShipmentTestFactory::new()->store();

        // When
        ($this->processor)(new CustomerErasedIntegrationEvent($customerId, self::ERASED_AT));

        // Then
        $finder = $this->service(ShipmentFinderInterface::class);
        $statusesById = [];
        foreach ($finder->byCustomer($customerId) as $result) {
            $statusesById[$result->id] = $result->status;
        }
        self::assertSame(ShipmentStatus::CANCELLED, $statusesById[$pending->id()->toString()]);
        self::assertSame(ShipmentStatus::DELIVERED, $statusesById[$delivered->id()->toString()]);

        $otherResults = array_values(iterator_to_array($finder->byCustomer($other->customerId())));
        self::assertSame(ShipmentStatus::PENDING, $otherResults[0]->status);
    }

    #[Test]
    public function itDoesNothingWhenNoShipmentsExistForTheCustomer(): void
    {
        // Given
        $customerId = Uuid::uuid7()->toString();

        // When
        ($this->processor)(new CustomerErasedIntegrationEvent($customerId, self::ERASED_AT));

        // Then
        self::expectNotToPerformAssertions();
    }
}
