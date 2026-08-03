<?php

declare(strict_types=1);

namespace Api\Tests\Resource;

use Api\Resource\ShipmentResource;
use Api\Tests\Support\AbstractApiTestCase;
use Fulfilment\Tests\Shipment\Support\Factory\ShipmentTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Symfony\Component\HttpFoundation\Response;

final class ShipmentResourceTest extends AbstractApiTestCase
{
    #[Test]
    public function itReturnsTheShipmentCreatedForAnOrder(): void
    {
        // Given
        $client = $this->authenticatedClient('fulfilment:supervise');
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->withTotalAmountInCents(1_999)->create();
        $this->store($order);
        $this->store(ShipmentTestFactory::new()->withOrderId($order->id()->toString())->withCustomerId($customerId)->create());

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
                    'orderId' => $order->id()->toString(),
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
        $client = $this->authenticatedClient('fulfilment:supervise');
        $dispatched = ShipmentTestFactory::new()->dispatched()->create();
        $this->store($dispatched);
        $this->store(ShipmentTestFactory::new()->create());

        // When
        $client->request('GET', '/v1/fulfilment/shipments?status=dispatched');

        // Then
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(ShipmentResource::class);
        self::assertJsonContains([
            'totalItems' => 1,
            'member' => [['id' => $dispatched->id()->toString(), 'status' => 'dispatched']],
        ]);
    }

    #[Test]
    public function itFailsToReturnShipmentsForAnUnknownStatus(): void
    {
        // Given
        $client = $this->authenticatedClient('fulfilment:supervise');

        // When
        $client->request('GET', '/v1/fulfilment/shipments?status=teleported');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Test]
    public function itRejectsAnUnauthenticatedRequest(): void
    {
        // Given
        $client = self::jsonClient();

        // When
        $client->request('GET', '/v1/fulfilment/shipments');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function itRejectsACallerWithoutTheSuperviseGrant(): void
    {
        // Given
        $client = $this->authenticatedClient();

        // When
        $client->request('GET', '/v1/fulfilment/shipments');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    #[Test]
    public function itAcceptsADispatch(): void
    {
        // Given
        $client = $this->authenticatedClient('fulfilment:supervise');
        $shipment = ShipmentTestFactory::new()->create();
        $this->store($shipment);

        // When
        $client->request('POST', \sprintf('/v1/fulfilment/shipments/%s/dispatch', $shipment->id()->toString()));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->request('GET', '/v1/fulfilment/shipments');
        self::assertJsonContains(['member' => [['id' => $shipment->id()->toString(), 'status' => 'dispatched']]]);
    }

    #[Test]
    public function itRejectsADispatchOnAShipmentAlreadyDispatched(): void
    {
        // Given
        $client = $this->authenticatedClient('fulfilment:supervise');
        $shipment = ShipmentTestFactory::new()->dispatched()->create();
        $this->store($shipment);

        // When
        $client->request('POST', \sprintf('/v1/fulfilment/shipments/%s/dispatch', $shipment->id()->toString()));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    #[Test]
    public function itFailsToDispatchAnUnknownShipment(): void
    {
        // Given
        $client = $this->authenticatedClient('fulfilment:supervise');

        // When
        $client->request('POST', \sprintf('/v1/fulfilment/shipments/%s/dispatch', Uuid::uuid7()->toString()));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
