<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Infrastructure\EventStore;

use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Support\TestCase\AbstractIntegrationTestCase;

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
        $shipment = ShipmentBuilder::new()->create();

        // When
        $this->repository->save($shipment);
        $loaded = $this->repository->load($shipment->id);

        // Then
        self::assertSame($shipment->id->toString(), $loaded->id->toString());
    }

    #[Test]
    public function itThrowsWhenNotFound(): void
    {
        // Then
        $this->expectException(ShipmentNotFoundException::class);

        // When
        $this->repository->load(ShipmentId::generate());
    }

    #[Test]
    public function itHas(): void
    {
        // Given
        $shipment = ShipmentBuilder::new()->create();
        $this->repository->save($shipment);

        // When
        $exists = $this->repository->has($shipment->id);

        // Then
        self::assertTrue($exists);
    }

    #[Test]
    public function itHasNot(): void
    {
        // When
        $notExists = $this->repository->has(ShipmentId::generate());

        // Then
        self::assertFalse($notExists);
    }
}
