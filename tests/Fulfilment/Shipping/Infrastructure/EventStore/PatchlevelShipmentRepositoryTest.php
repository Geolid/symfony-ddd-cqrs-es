<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Infrastructure\EventStore;

use Fulfilment\Shipping\Domain\Exception\ShipmentNotFoundException;
use Fulfilment\Shipping\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
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
        $this->repository->load(ShipmentId::fromString(Uuid::uuid7()->toString()));
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
        $notExists = $this->repository->has(ShipmentId::fromString(Uuid::uuid7()->toString()));

        // Then
        self::assertFalse($notExists);
    }
}
