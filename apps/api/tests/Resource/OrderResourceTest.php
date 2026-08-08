<?php

declare(strict_types=1);

namespace Api\Tests\Resource;

use Api\Resource\OrderResource;
use Api\Tests\Support\AbstractApiTestCase;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
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
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity, 'sales.order:read');
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
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity, 'sales.order:read');

        // When
        $client->request('GET', \sprintf('/v1/sales/orders/%s', Uuid::uuid7()->toString()));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    #[Test]
    public function itReturnsTheOrders(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity, 'sales.order:read');
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
        $client = self::unauthenticatedClient();

        // When
        $client->request('GET', '/v1/sales/orders');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function itRejectsACallerWithoutTheReadGrant(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity);

        // When
        $client->request('GET', '/v1/sales/orders');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    #[Test]
    public function itRejectsAMalformedApiKey(): void
    {
        // Given
        $client = self::malformedApiKeyClient();

        // When
        $client->request('GET', '/v1/sales/orders');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function itRejectsAnInvalidApiKey(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->invalidApiKeyClient($identity);

        // When
        $client->request('GET', '/v1/sales/orders');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function itRejectsARevokedApiKey(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->revokedApiKeyClient($identity);

        // When
        $client->request('GET', '/v1/sales/orders');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function itRejectsAnExpiredApiKey(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->expiredApiKeyClient($identity);

        // When
        $client->request('GET', '/v1/sales/orders');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function itRejectsASuspendedIdentity(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity, 'sales.order:read');

        // When
        $client->request('GET', '/v1/sales/orders');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
