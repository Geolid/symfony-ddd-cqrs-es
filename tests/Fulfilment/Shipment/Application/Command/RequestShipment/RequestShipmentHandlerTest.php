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
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObject\Address;
use Shared\Domain\ValueObject\FullName;
use Shared\Domain\ValueObject\PostalAddress;
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
        $orderId = Uuid::uuid7()->toString();
        $id = ShipmentId::forOrder($orderId)->toString();

        // When
        $this->dispatch(new RequestShipment(
            $id,
            $orderId,
            Uuid::uuid7()->toString(),
            ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris', 'countryCode' => 'FR'],
        ));

        // Then
        $result = $this->service(ShipmentFinderInterface::class)->ofId($id);
        self::assertSame($id, $result->id);
        self::assertSame($orderId, $result->orderId);
        self::assertSame(ShipmentStatus::REQUESTED, $result->status);
        $shipment = $this->repository->load(ShipmentId::fromString($id));
        $shippingAddress = $shipment->shippingAddress;
        self::assertSame(
            ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris', 'countryCode' => 'FR'],
            [
                'firstName' => $shippingAddress->fullName->firstName,
                'lastName' => $shippingAddress->fullName->lastName,
                'street' => $shippingAddress->address->street,
                'postalCode' => $shippingAddress->address->postalCode,
                'city' => $shippingAddress->address->city,
                'countryCode' => $shippingAddress->address->countryCode->value,
            ],
        );
    }

    #[Test]
    public function itIgnoresWhenAlreadyRequested(): void
    {
        // Given
        $orderId = Uuid::uuid7()->toString();
        $customerId = Uuid::uuid7()->toString();
        $id = ShipmentId::forOrder($orderId)->toString();
        $shipment = ShipmentBuilder::new()
            ->withOrderId($orderId)
            ->withCustomerId($customerId)
            ->withShippingAddress(PostalAddress::of(FullName::of('Ada', 'Lovelace'), Address::of('12 rue des Lilas', '75001', 'Paris', 'FR')))
            ->create();
        $this->store($shipment);

        // When
        $this->dispatch(new RequestShipment(
            $id,
            $orderId,
            $customerId,
            ['firstName' => 'Someone', 'lastName' => 'Else', 'street' => '8 avenue Foch', 'postalCode' => '75116', 'city' => 'Paris', 'countryCode' => 'FR'],
        ));

        // Then
        $shippingAddress = $this->repository->load(ShipmentId::fromString($id))->shippingAddress;
        self::assertSame(
            ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'street' => '12 rue des Lilas', 'postalCode' => '75001', 'city' => 'Paris', 'countryCode' => 'FR'],
            [
                'firstName' => $shippingAddress->fullName->firstName,
                'lastName' => $shippingAddress->fullName->lastName,
                'street' => $shippingAddress->address->street,
                'postalCode' => $shippingAddress->address->postalCode,
                'city' => $shippingAddress->address->city,
                'countryCode' => $shippingAddress->address->countryCode->value,
            ],
        );
    }
}
