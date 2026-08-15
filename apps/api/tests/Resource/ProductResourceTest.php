<?php

declare(strict_types=1);

namespace Api\Tests\Resource;

use Api\Resource\ProductResource;
use Api\Tests\Support\AbstractApiTestCase;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use Iam\Tests\Identity\Support\Factory\IdentityTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

final class ProductResourceTest extends AbstractApiTestCase
{
    #[Test]
    public function itReturnsAProduct(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $client = $this->authenticatedClient($identity, 'catalog.product:read');
        $product = ProductTestFactory::new()->withLabel('Wireless mouse')->withUnitAmountInCents(2_999)->store();

        // When
        $client->request('GET', \sprintf('/v1/catalog/products/%s', $product->id()->toString()));

        // Then
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertMatchesResourceItemJsonSchema(ProductResource::class);
        self::assertJsonContains([
            'id' => $product->id()->toString(),
            'label' => 'Wireless mouse',
            'unitAmountInCents' => 2_999,
        ]);
    }

    #[Test]
    public function itFailsToReturnAnUnknownProduct(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $client = $this->authenticatedClient($identity, 'catalog.product:read');

        // When
        $client->request('GET', '/v1/catalog/products/'.Uuid::uuid7()->toString());

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    #[Test]
    public function itReturnsTheProducts(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $client = $this->authenticatedClient($identity, 'catalog.product:read');
        ProductTestFactory::new()->withLabel('Wireless mouse')->store();
        ProductTestFactory::new()->withLabel('Delisted keyboard')->delisted()->store();

        // When
        $client->request('GET', '/v1/catalog/products');

        // Then
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertMatchesResourceCollectionJsonSchema(ProductResource::class);
        self::assertJsonContains(['totalItems' => 1]);
    }

    #[Test]
    public function itRejectsAnUnauthenticatedRequest(): void
    {
        // Given
        $client = self::unauthenticatedClient();

        // When
        $client->request('GET', '/v1/catalog/products');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function itRejectsACallerWithoutTheReadGrant(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $client = $this->authenticatedClient($identity);

        // When
        $client->request('GET', '/v1/catalog/products');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    #[Test]
    public function itRejectsAMalformedApiKey(): void
    {
        // Given
        $client = self::malformedApiKeyClient();

        // When
        $client->request('GET', '/v1/catalog/products');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function itRejectsAnInvalidApiKey(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $client = $this->invalidApiKeyClient($identity);

        // When
        $client->request('GET', '/v1/catalog/products');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function itRejectsARevokedApiKey(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $client = $this->revokedApiKeyClient($identity);

        // When
        $client->request('GET', '/v1/catalog/products');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function itRejectsAnExpiredApiKey(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $client = $this->expiredApiKeyClient($identity);

        // When
        $client->request('GET', '/v1/catalog/products');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function itRejectsASuspendedIdentity(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->suspended()->store();
        $client = $this->authenticatedClient($identity, 'catalog.product:read');

        // When
        $client->request('GET', '/v1/catalog/products');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function itAcceptsAProductToListForSale(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $client = $this->authenticatedClient($identity, 'catalog.product:write');

        // When
        $response = $client->request('POST', '/v1/catalog/products', [
            'json' => ['label' => 'Wireless mouse', 'unitAmountInCents' => 2_999],
        ]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertMatchesResourceItemJsonSchema(ProductResource::class);
        self::assertJsonContains([
            'label' => 'Wireless mouse',
            'unitAmountInCents' => 2_999,
        ]);
        self::assertNotEmpty($response->toArray()['id']);
    }

    #[Test]
    public function itFailsToAcceptANegativeUnitAmount(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $client = $this->authenticatedClient($identity, 'catalog.product:write');

        // When
        $client->request('POST', '/v1/catalog/products', [
            'json' => ['label' => 'Wireless mouse', 'unitAmountInCents' => -1],
        ]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Test]
    public function itFailsToAcceptABlankLabel(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $client = $this->authenticatedClient($identity, 'catalog.product:write');

        // When
        $client->request('POST', '/v1/catalog/products', [
            'json' => ['label' => '  ', 'unitAmountInCents' => 2_999],
        ]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Test]
    public function itRejectsACreationWithoutTheWriteGrant(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $client = $this->authenticatedClient($identity, 'catalog.product:read');

        // When
        $client->request('POST', '/v1/catalog/products', [
            'json' => ['label' => 'Wireless mouse', 'unitAmountInCents' => 2_999],
        ]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    #[Test]
    public function itAcceptsAReprice(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $client = $this->authenticatedClient($identity, 'catalog.product:write', 'catalog.product:read');
        $product = ProductTestFactory::new()->withUnitAmountInCents(2_999)->store();

        // When
        $client->request('POST', \sprintf('/v1/catalog/products/%s/reprice', $product->id()->toString()), [
            'json' => ['unitAmountInCents' => 3_499],
        ]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->request('GET', \sprintf('/v1/catalog/products/%s', $product->id()->toString()));
        self::assertJsonContains(['unitAmountInCents' => 3_499]);
    }

    #[Test]
    public function itFailsToAcceptANegativeReprice(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $client = $this->authenticatedClient($identity, 'catalog.product:write');
        $product = ProductTestFactory::new()->store();

        // When
        $client->request('POST', \sprintf('/v1/catalog/products/%s/reprice', $product->id()->toString()), [
            'json' => ['unitAmountInCents' => -1],
        ]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Test]
    public function itFailsToRepriceAnUnknownProduct(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $client = $this->authenticatedClient($identity, 'catalog.product:write');

        // When
        $client->request('POST', \sprintf('/v1/catalog/products/%s/reprice', Uuid::uuid7()->toString()), [
            'json' => ['unitAmountInCents' => 3_499],
        ]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    #[Test]
    public function itRejectsARepriceWithoutTheWriteGrant(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $client = $this->authenticatedClient($identity, 'catalog.product:read');
        $product = ProductTestFactory::new()->store();

        // When
        $client->request('POST', \sprintf('/v1/catalog/products/%s/reprice', $product->id()->toString()), [
            'json' => ['unitAmountInCents' => 3_499],
        ]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    #[Test]
    public function itAcceptsADelisting(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $client = $this->authenticatedClient($identity, 'catalog.product:write', 'catalog.product:read');
        $product = ProductTestFactory::new()->store();

        // When
        $client->request('POST', \sprintf('/v1/catalog/products/%s/delist', $product->id()->toString()));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->request('GET', \sprintf('/v1/catalog/products/%s', $product->id()->toString()));
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    #[Test]
    public function itFailsToDelistAnUnknownProduct(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $client = $this->authenticatedClient($identity, 'catalog.product:write');

        // When
        $client->request('POST', \sprintf('/v1/catalog/products/%s/delist', Uuid::uuid7()->toString()));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    #[Test]
    public function itAcceptsADelistingOnAProductAlreadyDelisted(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $client = $this->authenticatedClient($identity, 'catalog.product:write');
        $product = ProductTestFactory::new()->delisted()->store();

        // When
        $client->request('POST', \sprintf('/v1/catalog/products/%s/delist', $product->id()->toString()));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    #[Test]
    public function itRejectsADelistingWithoutTheWriteGrant(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->store();
        $client = $this->authenticatedClient($identity, 'catalog.product:read');
        $product = ProductTestFactory::new()->store();

        // When
        $client->request('POST', \sprintf('/v1/catalog/products/%s/delist', $product->id()->toString()));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
