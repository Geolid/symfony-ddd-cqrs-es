<?php

declare(strict_types=1);

namespace Api\Tests\Resource;

use Api\Resource\OrderResource;
use Api\Tests\Support\AbstractApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

final class OrderResourceTest extends AbstractApiTestCase
{
    #[Test]
    public function itAcceptsAnOrder(): void
    {
        // Given
        $client = self::jsonClient();
        $customerId = Uuid::uuid7()->toString();

        // When
        $response = $client->request('POST', '/v1/sales/orders', [
            'json' => ['customerId' => $customerId, 'totalAmountInCents' => 3_500],
        ]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertMatchesResourceItemJsonSchema(OrderResource::class);
        self::assertJsonContains([
            'customerId' => $customerId,
            'totalAmountInCents' => 3_500,
            'status' => 'placed',
        ]);
        self::assertNotEmpty($response->toArray()['id']);
    }

    #[Test]
    public function itFailsToAcceptANegativeAmount(): void
    {
        // Given
        $client = self::jsonClient();

        // When
        $client->request('POST', '/v1/sales/orders', [
            'json' => ['customerId' => Uuid::uuid7()->toString(), 'totalAmountInCents' => -1],
        ]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Test]
    public function itFailsToAcceptAMalformedCustomerId(): void
    {
        // Given
        $client = self::jsonClient();

        // When
        $client->request('POST', '/v1/sales/orders', [
            'json' => ['customerId' => 'not-a-uuid', 'totalAmountInCents' => 3_500],
        ]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Test]
    public function itReturnsAnOrder(): void
    {
        // Given
        $client = self::jsonClient();
        $customerId = Uuid::uuid7()->toString();
        $id = $this->placeOrder($client, $customerId, 3_500);

        // When
        $client->request('GET', \sprintf('/v1/sales/orders/%s', $id));

        // Then
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertMatchesResourceItemJsonSchema(OrderResource::class);
        self::assertJsonContains([
            'id' => $id,
            'customerId' => $customerId,
            'totalAmountInCents' => 3_500,
            'status' => 'placed',
        ]);
    }

    #[Test]
    public function itFailsToReturnAnUnknownOrder(): void
    {
        // Given
        $client = self::jsonClient();

        // When
        $client->request('GET', \sprintf('/v1/sales/orders/%s', Uuid::uuid7()->toString()));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    #[Test]
    public function itReturnsTheOrders(): void
    {
        // Given
        $client = self::jsonClient();
        $first = Uuid::uuid7()->toString();
        $second = Uuid::uuid7()->toString();
        $this->placeOrder($client, $first, 1_000);
        $this->placeOrder($client, $second, 2_000);

        // When
        $client->request('GET', '/v1/sales/orders');

        // Then
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertMatchesResourceCollectionJsonSchema(OrderResource::class);
        self::assertJsonContains([
            'totalItems' => 2,
            'member' => [
                ['customerId' => $second, 'totalAmountInCents' => 2_000],
                ['customerId' => $first, 'totalAmountInCents' => 1_000],
            ],
        ]);
    }

    #[Test]
    public function itAcceptsACancellation(): void
    {
        // Given
        $client = self::jsonClient();
        $id = $this->placeOrder($client, Uuid::uuid7()->toString(), 3_500);

        // When
        $client->request('POST', \sprintf('/v1/sales/orders/%s/cancel', $id));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->request('GET', \sprintf('/v1/sales/orders/%s', $id));
        self::assertJsonContains(['id' => $id, 'status' => 'cancelled']);
    }

    #[Test]
    public function itRejectsACancellationOnAnOrderAlreadyCancelled(): void
    {
        // Given
        $client = self::jsonClient();
        $id = $this->placeOrder($client, Uuid::uuid7()->toString(), 3_500);
        $client->request('POST', \sprintf('/v1/sales/orders/%s/cancel', $id));

        // When
        $client->request('POST', \sprintf('/v1/sales/orders/%s/cancel', $id));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    private function placeOrder(Client $client, string $customerId, int $totalAmountInCents): string
    {
        $response = $client->request('POST', '/v1/sales/orders', [
            'json' => ['customerId' => $customerId, 'totalAmountInCents' => $totalAmountInCents],
        ]);

        return $response->toArray()['id'];
    }
}
