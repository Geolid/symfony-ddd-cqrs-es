<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Policy;

use Crm\Customer\Application\IntegrationEvent\CustomerErased\CustomerErasedIntegrationEvent;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Application\Policy\ApproveShipmentsErasureOnCustomerErased;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\ErasureStatus;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ApproveShipmentsErasureOnCustomerErasedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itApproves(): void
    {
        // Given
        $other = ShipmentBuilder::new()->create();
        $customerId = Uuid::uuid7()->toString();
        $shipment = ShipmentBuilder::new()->withCustomerId($customerId)->create();
        $this->store($other, $shipment);

        // When
        $this->trigger(ApproveShipmentsErasureOnCustomerErased::class, new CustomerErasedIntegrationEvent($customerId, Clock::get()->now()));

        // Then
        $finder = $this->service(ShipmentFinderInterface::class);
        $results = iterator_to_array($finder->byCustomer($customerId), false);
        self::assertSame($shipment->id->toString(), $results[0]->id);
        self::assertSame(ErasureStatus::APPROVED, $results[0]->erasureStatus);

        $otherResult = $finder->ofId($other->id->toString());
        self::assertSame(ErasureStatus::RETAINED, $otherResult->erasureStatus);
    }
}
