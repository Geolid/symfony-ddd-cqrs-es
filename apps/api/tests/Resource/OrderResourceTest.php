<?php

declare(strict_types=1);

namespace Api\Tests\Resource;

use Api\Resource\OrderResource;
use Api\Tests\Support\AbstractApiTestCase;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Sales\Tests\Order\Support\Factory\OrderTestFactory;
use Symfony\Component\HttpFoundation\Response;

final class OrderResourceTest extends AbstractApiTestCase
{
    #[Test]
    public function itReturnsAnOrder(): void
    {
        // Given
        $client = $this->authenticatedClient('sales:read');
        $customerId = Uuid::uuid7()->toString();
        $order = OrderTestFactory::new()->withCustomerId($customerId)->withTotalAmountInCents(1_999)->create();
        $this->store($order);

        // When
        $client->request('GET', \sprintf('/v1/sales/orders/%s', $order->id()->toString()));

        // Then
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertMatchesResourceItemJsonSchema(OrderResource::class);
        self::assertJsonContains([
            'id' => $order->id()->toString(),
            'customerId' => $customerId,
            'totalAmountInCents' => 1_999,
            'status' => 'placed',
        ]);
    }

    #[Test]
    public function itFailsToReturnAnUnknownOrder(): void
    {
        // Given
        $client = $this->authenticatedClient('sales:read');

        // When
        $client->request('GET', \sprintf('/v1/sales/orders/%s', Uuid::uuid7()->toString()));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    #[Test]
    public function itReturnsTheOrders(): void
    {
        // Given
        $client = $this->authenticatedClient('sales:read');
        $this->store(OrderTestFactory::new()->withTotalAmountInCents(1_999)->create());

        // When
        $client->request('GET', '/v1/sales/orders');

        // Then
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertMatchesResourceCollectionJsonSchema(OrderResource::class);
        self::assertJsonContains(['totalItems' => 1]);
    }

    #[Test]
    public function itRejectsAnUnauthenticatedRequest(): void
    {
        // Given
        $client = self::jsonClient();

        // When
        $client->request('GET', '/v1/sales/orders');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function itRejectsACallerWithoutTheReadGrant(): void
    {
        // Given
        $client = $this->authenticatedClient();

        // When
        $client->request('GET', '/v1/sales/orders');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
