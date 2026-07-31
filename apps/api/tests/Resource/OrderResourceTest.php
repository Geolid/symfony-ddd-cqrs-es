<?php

declare(strict_types=1);

namespace Api\Tests\Resource;

use Api\Resource\OrderResource;
use Api\Tests\Support\AbstractApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Customer\Support\Factory\CustomerTestFactory;
use Symfony\Component\HttpFoundation\Response;

final class OrderResourceTest extends AbstractApiTestCase
{
    #[Test]
    public function itAcceptsAnOrder(): void
    {
        // Given
        $client = self::jsonClient();
        $customerId = $this->registeredCustomer();

        // When
        $response = $client->request('POST', '/v1/sales/orders', [
            'json' => ['customerId' => $customerId, 'lines' => self::lines()],
        ]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertMatchesResourceItemJsonSchema(OrderResource::class);
        self::assertJsonContains([
            'customerId' => $customerId,
            'totalAmountInCents' => 1_999,
            'status' => 'placed',
        ]);
        self::assertNotEmpty($response->toArray()['id']);
    }

    #[Test]
    public function itFailsToAcceptANegativeUnitAmount(): void
    {
        // Given
        $client = self::jsonClient();

        // When
        $client->request('POST', '/v1/sales/orders', [
            'json' => ['customerId' => $this->registeredCustomer(), 'lines' => [
                ['label' => 'Saucer', 'quantity' => 3, 'unitAmountInCents' => -1],
            ]],
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
            'json' => ['customerId' => 'not-a-uuid', 'lines' => self::lines()],
        ]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Test]
    public function itFailsToAcceptAnUnregisteredBuyer(): void
    {
        // Given
        $client = self::jsonClient();

        // When
        $client->request('POST', '/v1/sales/orders', [
            'json' => ['customerId' => Uuid::uuid7()->toString(), 'lines' => self::lines()],
        ]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Test]
    public function itReturnsAnOrder(): void
    {
        // Given
        $client = self::jsonClient();
        $customerId = $this->registeredCustomer();
        $id = $this->placeOrder($client, $customerId);

        // When
        $client->request('GET', \sprintf('/v1/sales/orders/%s', $id));

        // Then
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertMatchesResourceItemJsonSchema(OrderResource::class);
        self::assertJsonContains([
            'id' => $id,
            'customerId' => $customerId,
            'totalAmountInCents' => 1_999,
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
        $first = $this->registeredCustomer();
        $second = $this->registeredCustomer();
        $this->placeOrder($client, $first);
        $this->placeOrder($client, $second);

        // When
        $client->request('GET', '/v1/sales/orders');

        // Then
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertMatchesResourceCollectionJsonSchema(OrderResource::class);
        self::assertJsonContains([
            'totalItems' => 2,
            'member' => [
                ['customerId' => $second, 'totalAmountInCents' => 1_999],
                ['customerId' => $first, 'totalAmountInCents' => 1_999],
            ],
        ]);
    }

    #[Test]
    public function itAcceptsACancellation(): void
    {
        // Given
        $client = self::jsonClient();
        $id = $this->placeOrder($client, $this->registeredCustomer());

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
        $id = $this->placeOrder($client, $this->registeredCustomer());
        $client->request('POST', \sprintf('/v1/sales/orders/%s/cancel', $id));

        // When
        $client->request('POST', \sprintf('/v1/sales/orders/%s/cancel', $id));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
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
            'json' => ['customerId' => $customerId, 'lines' => self::lines()],
        ]);

        return $response->toArray()['id'];
    }

    /**
     * @return list<array{label: string, quantity: int, unitAmountInCents: int}>
     */
    private static function lines(): array
    {
        return [
                ['label' => 'Espresso cups, set of 6', 'quantity' => 1, 'unitAmountInCents' => 1_750],
                ['label' => 'Saucer', 'quantity' => 3, 'unitAmountInCents' => 83],
            ];
    }
}
