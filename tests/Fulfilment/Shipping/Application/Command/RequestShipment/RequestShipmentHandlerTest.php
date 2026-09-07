<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipping\Application\Command\RequestShipment;

use Fulfilment\Shipping\Application\Command\RequestShipment\RequestShipment;
use Fulfilment\Shipping\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipping\Application\ShipmentStatus;
use Fulfilment\Shipping\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipping\Domain\ValueObject\ShipmentId;
use Fulfilment\Tests\Shipping\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\TestCase\AbstractIntegrationTestCase;

final class RequestShipmentHandlerTest extends AbstractIntegrationTestCase
{
    private ShipmentRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(ShipmentRepositoryInterface::class);
    }

    #[Test]
    public function itRequests(): void
    {
        // Given
        $id = Uuid::uuid7()->toString();
        $orderId = ShipmentBuilder::sample('orderId');
        $buyerId = ShipmentBuilder::sample('buyerId');
        $originData = ShipmentBuilder::sample('origin')->toArray();
        $destinationData = ShipmentBuilder::sample('destination')->toArray();

        // When
        $this->dispatch(new RequestShipment($id, $orderId, $buyerId, $originData, $destinationData));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame($orderId, $result->orderId);
        self::assertSame(ShipmentStatus::REQUESTED, $result->status);
        $shipment = $this->repository->load(ShipmentId::fromString($id));
        $shipmentDestination = $shipment->destination->toArray();
        self::assertSame($destinationData, $shipmentDestination);
    }

    #[Test]
    public function itIgnoresWhenAlreadyRequested(): void
    {
        // Given
        $builder = ShipmentBuilder::new();
        $shipment = $builder->create();
        $this->store($shipment);
        $attemptedDestination = ShipmentBuilder::sample('destination');

        // When
        $this->dispatch(new RequestShipment(
            $shipment->id->toString(),
            $builder['orderId'],
            $builder['buyerId'],
            $builder['origin']->toArray(),
            $attemptedDestination->toArray(),
        ));

        // Then
        $result = $this->repository->load($shipment->id);
        $resultDestination = $result->destination->toArray();
        $originalDestination = $builder['destination']->toArray();
        self::assertSame($originalDestination, $resultDestination);
    }
}
