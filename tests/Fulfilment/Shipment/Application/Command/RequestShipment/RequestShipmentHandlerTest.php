<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\RequestShipment;

use Fulfilment\Shipment\Application\Command\RequestShipment\RequestShipment;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\ShipmentStatus;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Tests\Shipment\Support\Builder\ShipmentBuilder;
use PHPUnit\Framework\Attributes\Test;
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
        $id = ShipmentId::generate()->toString();
        $reference = ShipmentBuilder::sample('reference');
        $customerId = ShipmentBuilder::sample('customerId');
        $originData = ShipmentBuilder::sample('origin')->toArray();
        $destinationData = ShipmentBuilder::sample('destination')->toArray();

        // When
        $this->dispatch(new RequestShipment($id, $reference, $customerId, $originData, $destinationData));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame($reference, $result->reference);
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
            $builder['reference'],
            $builder['customerId'],
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
