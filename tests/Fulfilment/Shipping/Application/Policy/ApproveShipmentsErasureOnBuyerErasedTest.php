<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Policy;

use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Application\Policy\ApproveShipmentsErasureOnBuyerErased;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Buyer\Application\IntegrationEvent\BuyerErased\BuyerErasedIntegrationEvent;
use Shared\Application\ErasureStatus;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ApproveShipmentsErasureOnBuyerErasedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itApproves(): void
    {
        // Given
        $other = ShipmentBuilder::new()->create();
        $buyerId = Uuid::uuid7()->toString();
        $shipment = ShipmentBuilder::new()->withBuyerId($buyerId)->create();
        $this->store($other, $shipment);

        // When
        $this->trigger(ApproveShipmentsErasureOnBuyerErased::class, new BuyerErasedIntegrationEvent($buyerId, Clock::get()->now()));

        // Then
        $finder = $this->service(ShipmentFinderInterface::class);
        $results = iterator_to_array($finder->byBuyer($buyerId), false);
        self::assertSame($shipment->id->toString(), $results[0]->id);
        self::assertSame(ErasureStatus::APPROVED, $results[0]->erasureStatus);

        $otherResult = $finder->ofId($other->id->toString());
        self::assertSame(ErasureStatus::RETAINED, $otherResult->erasureStatus);
    }
}
