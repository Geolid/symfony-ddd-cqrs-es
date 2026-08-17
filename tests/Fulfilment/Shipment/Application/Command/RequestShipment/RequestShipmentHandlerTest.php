<?php

declare(strict_types=1);

namespace Fulfilment\Tests\Shipment\Application\Command\RequestShipment;

use Fulfilment\Shipment\Application\Command\RequestShipment\RequestShipment;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentFinderInterface;
use Fulfilment\Shipment\Application\Status\ShipmentStatus;
use Fulfilment\Shipment\Domain\Repository\ShipmentRepositoryInterface;
use Fulfilment\Shipment\Domain\ValueObject\ShipmentId;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
use Support\AbstractIntegrationTestCase;

final class RequestShipmentHandlerTest extends AbstractIntegrationTestCase
{
    private ShipmentRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->service(ShipmentRepositoryInterface::class);
    }

    #[Test]
    public function itRequestsAShipmentForAnOrder(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $id = ShipmentId::forOrder($orderId)->toString();

        // When
        $this->dispatch(new RequestShipment(
            $id,
            $orderId,
            Uuid::uuid7()->toString(),
            ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris'],
        ));

        // Then
        $results = iterator_to_array($this->service(ShipmentFinderInterface::class), false);
        self::assertCount(1, $results);
        self::assertSame($id, $results[0]->id);
        self::assertSame($orderId, $results[0]->orderId);
        self::assertSame(ShipmentStatus::REQUESTED, $results[0]->status);
        $shipment = $this->repository->load(ShipmentId::fromString($id));
        $shippingAddress = $shipment->shippingAddress();
        self::assertSame(
            ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris'],
            [
                'firstName' => $shippingAddress->fullName->firstName,
                'lastName' => $shippingAddress->fullName->lastName,
                'street' => $shippingAddress->address->street,
                'postalCode' => $shippingAddress->address->postalCode,
                'city' => $shippingAddress->address->city,
            ],
        );
    }

    #[Test]
    public function itIgnoresAnAlreadyRequestedShipment(): void
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
        $this->dispatch(new RequestShipment(
            $id,
            $orderId,
            $customerId,
            ['firstName' => 'Someone', 'lastName' => 'Else', 'street' => '8 avenue Foch', 'postalCode' => '75116', 'city' => 'Paris'],
        ));

        // Then
        $shippingAddress = $this->repository->load(ShipmentId::fromString($id))->shippingAddress();
        self::assertSame(
            ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris'],
            [
                'firstName' => $shippingAddress->fullName->firstName,
                'lastName' => $shippingAddress->fullName->lastName,
                'street' => $shippingAddress->address->street,
                'postalCode' => $shippingAddress->address->postalCode,
                'city' => $shippingAddress->address->city,
            ],
        );
    }
}
