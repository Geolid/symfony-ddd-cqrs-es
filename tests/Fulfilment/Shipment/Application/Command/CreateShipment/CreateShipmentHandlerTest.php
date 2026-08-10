<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\CreateShipment;

use Fulfilment\Shipment\Application\Command\CreateShipment\CreateShipment;
use Fulfilment\Shipment\Application\Enum\ShipmentStatus;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Support\AbstractIntegrationTestCase;

final class CreateShipmentHandlerTest extends AbstractIntegrationTestCase
{
    private ShipmentRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(ShipmentRepositoryInterface::class);
    }

    #[Test]
    public function itCreatesAShipmentForAnOrder(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $id = ShipmentId::forOrder($orderId)->toString();

        // When
        $this->dispatch(new CreateShipment($id, $orderId, Uuid::uuid7()->toString(), 'buyer@example.com'));

        // Then
        $results = array_values(iterator_to_array($this->service(ShipmentFinderInterface::class)));
        self::assertCount(1, $results);
        self::assertSame($id, $results[0]->id);
        self::assertSame($orderId, $results[0]->orderId);
        self::assertSame(ShipmentStatus::PENDING, $results[0]->status);
        self::assertSame(
            'buyer@example.com',
            $this->repository->load(ShipmentId::fromString($id))->customerAddress(),
        );
    }

    #[Test]
    public function itKeepsTheShipmentItAlreadyCreated(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $customerId = Uuid::uuid7()->toString();
        $id = ShipmentId::forOrder($orderId)->toString();
        $this->dispatch(new CreateShipment($id, $orderId, $customerId, 'buyer@example.com'));

        // When
        $this->dispatch(new CreateShipment($id, $orderId, $customerId, 'someone.else@example.com'));

        // Then
        self::assertSame(
            'buyer@example.com',
            $this->repository->load(ShipmentId::fromString($id))->customerAddress(),
        );
    }
}
