<?php

declare(strict_types=1);

namespace Api\Tests\Resource;

use Api\Resource\ShipmentResource;
use Api\Tests\Support\AbstractApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Fulfilment\Shipment\Application\Command\CreateShipment\CreateShipment;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

final class ShipmentResourceTest extends AbstractApiTestCase
{
    #[Test]
    public function itReturnsTheShipmentCreatedForAnOrder(): void
    {
        // Given
        $client = self::jsonClient();
        $orderId = $this->placeOrder($client, 3_500);
        $this->createShipment($orderId);

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
                    'customerId' => 'customer-1',
                    'orderTotalInCents' => 3_500,
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
        $dispatched = $this->createShipment($this->placeOrder($client, 1_000));
        $this->createShipment($this->placeOrder($client, 2_000));
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
        $id = $this->createShipment($this->placeOrder($client, 2_000));

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
        $id = $this->createShipment($this->placeOrder($client, 2_000));
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
        $client->request('POST', '/v1/fulfilment/shipments/00000000-0000-0000-0000-000000000000/dispatch');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function placeOrder(Client $client, int $totalAmountInCents): string
    {
        $response = $client->request('POST', '/v1/sales/orders', [
            'json' => ['customerId' => 'customer-1', 'totalAmountInCents' => $totalAmountInCents],
        ]);

        return $response->toArray()['id'];
    }

    private function createShipment(string $orderId): string
    {
        $id = Uuid::uuid7()->toString();

        $this->dispatch(new CreateShipment($id, $orderId, 'customer-1'));

        return $id;
    }
}
