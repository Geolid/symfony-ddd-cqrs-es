<?php

declare(strict_types=1);

namespace Api\Tests\Resource;

use Api\Resource\ShipmentResource;
use Api\Tests\Support\AbstractApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Symfony\Component\HttpFoundation\Response;

final class ShipmentResourceTest extends AbstractApiTestCase
{
    #[Test]
    public function itReturnsTheShipmentCreatedForAnOrder(): void
    {
        // Given
        $client = self::jsonClient();
        $customerId = $this->registeredCustomer();
        $orderId = $this->placeOrder($client, $customerId);
        $this->createShipment($orderId, $customerId);

        // When
        $client->request('GET', '/v1/fulfilment/shipments');

        // Then
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertMatchesResourceCollectionJsonSchema(ShipmentResource::class);
        self::assertJsonContains([
            'totalItems' => 1,
            'member' => [
                [
                    'orderId' => $orderId,
                    'customerId' => $customerId,
                    'orderTotalInCents' => 1_999,
                    'status' => 'pending',
                ],
            ],
        ]);
    }

    #[Test]
    public function itReturnsShipmentsFilteredByStatus(): void
    {
        // Given
        $client = self::jsonClient();
        $dispatched = $this->shipmentForNewOrder($client);
        $this->shipmentForNewOrder($client);
        $client->request('POST', \sprintf('/v1/fulfilment/shipments/%s/dispatch', $dispatched));

        // When
        $client->request('GET', '/v1/fulfilment/shipments?status=dispatched');

        // Then
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(ShipmentResource::class);
        self::assertJsonContains([
            'totalItems' => 1,
            'member' => [['id' => $dispatched, 'status' => 'dispatched']],
        ]);
    }

    #[Test]
    public function itFailsToReturnShipmentsForAnUnknownStatus(): void
    {
        // Given
        $client = self::jsonClient();

        // When
        $client->request('GET', '/v1/fulfilment/shipments?status=teleported');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Test]
    public function itAcceptsADispatch(): void
    {
        // Given
        $client = self::jsonClient();
        $id = $this->shipmentForNewOrder($client);

        // When
        $client->request('POST', \sprintf('/v1/fulfilment/shipments/%s/dispatch', $id));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->request('GET', '/v1/fulfilment/shipments');
        self::assertJsonContains(['member' => [['id' => $id, 'status' => 'dispatched']]]);
    }

    #[Test]
    public function itRejectsADispatchOnAShipmentAlreadyDispatched(): void
    {
        // Given
        $client = self::jsonClient();
        $id = $this->shipmentForNewOrder($client);
        $client->request('POST', \sprintf('/v1/fulfilment/shipments/%s/dispatch', $id));

        // When
        $client->request('POST', \sprintf('/v1/fulfilment/shipments/%s/dispatch', $id));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    #[Test]
    public function itFailsToDispatchAnUnknownShipment(): void
    {
        // Given
        $client = self::jsonClient();

        // When
        $client->request('POST', \sprintf('/v1/fulfilment/shipments/%s/dispatch', Uuid::uuid7()->toString()));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function shipmentForNewOrder(Client $client): string
    {
        $customerId = $this->registeredCustomer();

        return $this->createShipment($this->placeOrder($client, $customerId), $customerId);
    }

    private function registeredCustomer(): string
    {
        $customer = CustomerTestFactory::new()->create();

        $this->store($customer);

        return $customer->id()->toString();
    }

    private function placeOrder(Client $client, string $customerId): string
    {
        $response = $client->request('POST', '/v1/sales/orders', [
            'json' => ['customerId' => $customerId, 'lines' => [
                ['label' => 'Espresso cups, set of 6', 'quantity' => 1, 'unitAmountInCents' => 1_750],
                ['label' => 'Saucer', 'quantity' => 3, 'unitAmountInCents' => 83],
            ]],
        ]);

        return $response->toArray()['id'];
    }

    private function createShipment(string $orderId, string $customerId): string
    {
        $shipment = ShipmentTestFactory::new()
            ->withOrderId($orderId)
            ->withCustomerId($customerId)
            ->create();

        $this->store($shipment);

        return $shipment->id()->toString();
    }
}
