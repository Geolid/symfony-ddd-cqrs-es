<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\CreateShipment;

use Fulfilment\Shipment\Application\Command\CreateShipment\CreateShipment;
use Fulfilment\Shipment\Application\Enum\ShipmentStatus;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
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
        $this->dispatch(new CreateShipment(
            $id,
            $orderId,
            Uuid::uuid7()->toString(),
            'Ada',
            'Lovelace',
            '12 rue des Lilas',
            '75001',
            'Paris',
        ));

        // Then
        $results = iterator_to_array($this->service(ShipmentFinderInterface::class), false);
        self::assertCount(1, $results);
        self::assertSame($id, $results[0]->id);
        self::assertSame($orderId, $results[0]->orderId);
        self::assertSame(ShipmentStatus::PENDING, $results[0]->status);
        $shipment = $this->repository->load(ShipmentId::fromString($id));
        self::assertSame('12 rue des Lilas', $shipment->shippingAddress()->address->street);
    }

    #[Test]
    public function itKeepsTheShipmentItAlreadyCreated(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $customerId = Uuid::uuid7()->toString();
        $id = ShipmentId::forOrder($orderId)->toString();
        ShipmentTestFactory::new()
            ->withOrderId($orderId)
            ->withCustomerId($customerId)
            ->withShippingAddress(PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris')))
            ->store();

        // When
        $this->dispatch(new CreateShipment(
            $id,
            $orderId,
            $customerId,
            'Someone',
            'Else',
            '8 avenue Foch',
            '75116',
            'Paris',
        ));

        // Then
        self::assertSame(
            '12 rue des Lilas',
            $this->repository->load(ShipmentId::fromString($id))->shippingAddress()->address->street,
        );
    }
}
