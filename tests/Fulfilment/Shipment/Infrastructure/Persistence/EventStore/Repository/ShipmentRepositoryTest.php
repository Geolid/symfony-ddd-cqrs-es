<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\Persistence\EventStore\Repository;

use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class ShipmentRepositoryTest extends AbstractIntegrationTestCase
{
    private ShipmentRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(ShipmentRepositoryInterface::class);
    }

    #[Test]
    public function itLoadsASavedShipment(): void
    {
        // Given
        $shipment = ShipmentTestFactory::new()->tracked('ACME-4Q7X2K9')->create();

        // When
        $this->repository->save($shipment);

        // Then
        $id = $shipment->id();
        self::assertTrue($this->repository->has($id));
        self::assertSame('ACME-4Q7X2K9', $this->repository->load($id)->trackingReference()?->toString());
    }

    #[Test]
    public function itThrowsOnAnUnsavedShipment(): void
    {
        // Given
        $id = ShipmentId::forOrder(Uuid::uuid7()->toString());

        // Then
        self::assertFalse($this->repository->has($id));
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->repository->load($id);
    }
}
