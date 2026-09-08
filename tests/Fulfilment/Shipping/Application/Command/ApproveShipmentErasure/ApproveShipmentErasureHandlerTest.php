<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Command\ApproveShipmentErasure;

use Fulfilment\Shipping\Application\Command\ApproveShipmentErasure\ApproveShipmentErasure;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Application\ErasureStatus;
use Support\TestCase\AbstractIntegrationTestCase;

final class ApproveShipmentErasureHandlerTest extends AbstractIntegrationTestCase
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
        $shipment = ShipmentBuilder::new()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new ApproveShipmentErasure($shipment->id->toString()));

        // Then
        $result = $this->finder->ofId($shipment->id->toString());
        self::assertSame(ErasureStatus::APPROVED, $result->erasureStatus);
    }

    #[Test]
    public function itApprovesAndErasesWhenAlreadyDelivered(): void
    {
        // Given
        $shipment = ShipmentBuilder::new()->prepared()->manifested()->dispatched()->delivered()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new ApproveShipmentErasure($shipment->id->toString()));

        // Then
        $result = $this->finder->ofId($shipment->id->toString());
        self::assertSame(ErasureStatus::ERASED, $result->erasureStatus);
    }

    #[Test]
    public function itIgnoresWhenAlreadyApproved(): void
    {
        // Given
        $shipment = ShipmentBuilder::new()->erasureApproved()->create();
        $this->store($shipment);

        // When
        $this->dispatch(new ApproveShipmentErasure($shipment->id->toString()));

        // Then
        $result = $this->finder->ofId($shipment->id->toString());
        self::assertSame(ErasureStatus::APPROVED, $result->erasureStatus);
    }

    #[Test]
    public function itFailsWhenNotFound(): void
    {
        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->dispatch(new ApproveShipmentErasure(Uuid::uuid7()->toString()));
    }
}
