<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Policy;

use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Application\Policy\ApproveShipmentsErasureOnShopperErased;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\ErasureStatus;
use Shopping\Checkout\Application\IntegrationEvent\ShopperErased\ShopperErasedIntegrationEvent;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\Clock\Clock;

final class ApproveShipmentsErasureOnShopperErasedTest extends AbstractIntegrationTestCase
{
    #[Test]
    public function itApproves(): void
    {
        // Given
        $other = ShipmentBuilder::new()->create();
        $shopperId = Uuid::uuid7()->toString();
        $shipment = ShipmentBuilder::new()->withShopperId($shopperId)->create();
        $this->store($other, $shipment);

        // When
        $this->trigger(ApproveShipmentsErasureOnShopperErased::class, new ShopperErasedIntegrationEvent($shopperId, Clock::get()->now()));

        // Then
        $finder = $this->service(ShipmentFinderInterface::class);
        $results = iterator_to_array($finder->byShopper($shopperId), false);
        self::assertSame($shipment->id->toString(), $results[0]->id);
        self::assertSame(ErasureStatus::APPROVED, $results[0]->erasureStatus);

        $otherResult = $finder->ofId($other->id->toString());
        self::assertSame(ErasureStatus::RETAINED, $otherResult->erasureStatus);
    }
}
