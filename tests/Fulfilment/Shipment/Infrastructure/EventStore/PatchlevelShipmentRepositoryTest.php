<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\EventStore;

use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Support\AbstractIntegrationTestCase;

final class PatchlevelShipmentRepositoryTest extends AbstractIntegrationTestCase
{
    private ShipmentRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(ShipmentRepositoryInterface::class);
    }

    #[Test]
    public function itSavesAndLoads(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->create();

        // When
        $this->repository->save($shipment);

        // Then
        $id = $shipment->id;
        self::assertTrue($this->repository->has($id));
        $loaded = $this->repository->load($id);
        self::assertSame($shipment->id->toString(), $loaded->id->toString());
        self::assertSame($shipment->orderId, $loaded->orderId);
        self::assertSame($shipment->customerId, $loaded->customerId);
        self::assertTrue($shipment->shippingAddress->equals($loaded->shippingAddress));
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Given
        $id = ShipmentTestFactory::new()->create()->id;

        // Then
        self::assertFalse($this->repository->has($id));
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->repository->load($id);
    }
}
