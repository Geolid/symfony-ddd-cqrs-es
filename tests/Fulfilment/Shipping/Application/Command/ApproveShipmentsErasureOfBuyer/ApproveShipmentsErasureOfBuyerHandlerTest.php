<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Command\ApproveShipmentsErasureOfBuyer;

use Fulfilment\Shipping\Application\Command\ApproveShipmentsErasureOfBuyer\ApproveShipmentsErasureOfBuyer;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\ErasureStatus;
use Support\TestCase\AbstractIntegrationTestCase;

final class ApproveShipmentsErasureOfBuyerHandlerTest extends AbstractIntegrationTestCase
{
    private ShipmentFinderInterface $finder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finder = $this->service(ShipmentFinderInterface::class);
    }

    #[Test]
    public function itApproves(): void
    {
        // Given
        $otherBuyerId = Uuid::uuid7()->toString();
        $other = ShipmentBuilder::new()->withBuyerId($otherBuyerId)->create();

        $buyerId = Uuid::uuid7()->toString();
        $requested = ShipmentBuilder::new()->withBuyerId($buyerId)->create();
        $delivered = ShipmentBuilder::new()->withBuyerId($buyerId)->prepared()->manifested()->dispatched()->delivered()->create();
        $this->store($other, $requested, $delivered);

        // When
        $this->dispatch(new ApproveShipmentsErasureOfBuyer($buyerId));

        // Then
        $statusesById = [];
        foreach ($this->finder->byBuyer($buyerId) as $result) {
            $statusesById[$result->id] = $result->erasureStatus;
        }
        self::assertSame(ErasureStatus::APPROVED, $statusesById[$requested->id->toString()]);
        self::assertSame(ErasureStatus::ERASED, $statusesById[$delivered->id->toString()]);

        $otherResult = $this->finder->ofId($other->id->toString());
        self::assertSame(ErasureStatus::RETAINED, $otherResult->erasureStatus);
    }

    #[Test]
    public function itIgnoresWhenNoneExist(): void
    {
        // Given
        $buyerId = Uuid::uuid7()->toString();
        $otherBuyerId = Uuid::uuid7()->toString();
        $other = ShipmentBuilder::new()->withBuyerId($otherBuyerId)->create();
        $this->store($other);

        // When
        $this->dispatch(new ApproveShipmentsErasureOfBuyer($buyerId));

        // Then
        $otherResult = $this->finder->ofId($other->id->toString());
        self::assertSame(ErasureStatus::RETAINED, $otherResult->erasureStatus);
    }
}
