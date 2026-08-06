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
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity, 'catalog:read');
        $product = ProductTestFactory::new()->withLabel('Wireless mouse')->withUnitAmountInCents(2_999)->create();
        $this->store($product);

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
            'delisted' => false,
        ]);
    }

    #[Test]
    public function itFailsToReturnAnUnknownProduct(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity, 'catalog:read');

        // When
        $client->request('GET', '/v1/catalog/products/'.Uuid::uuid7()->toString());

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    #[Test]
    public function itReturnsTheProducts(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity, 'catalog:read');
        $this->store(ProductTestFactory::new()->withLabel('Wireless mouse')->create());
        $this->store(ProductTestFactory::new()->withLabel('Delisted keyboard')->delisted()->create());

        // When
        $client->request('GET', '/v1/catalog/products');

        // Then
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertMatchesResourceCollectionJsonSchema(ProductResource::class);
        self::assertJsonContains(['totalItems' => 1]);
    }

    #[Test]
    public function itReturnsTheProductsIncludingDelistedOnes(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity, 'catalog:read');
        $this->store(ProductTestFactory::new()->withLabel('Wireless mouse')->create());
        $this->store(ProductTestFactory::new()->withLabel('Delisted keyboard')->delisted()->create());

        // When
        $client->request('GET', '/v1/catalog/products?includeDelisted=true');

        // Then
        self::assertResponseIsSuccessful();
        self::assertJsonContains(['totalItems' => 2]);
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
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
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
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->invalidApiKeyClient($identity);

        // When
        $client->request('GET', '/v1/catalog/products');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function itAcceptsAProductToListForSale(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity, 'catalog:write');

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
            'delisted' => false,
        ]);
        self::assertNotEmpty($response->toArray()['id']);
    }

    #[Test]
    public function itFailsToAcceptANegativeUnitAmount(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity, 'catalog:write');

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
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity, 'catalog:write');

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
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity, 'catalog:read');

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
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity, 'catalog:write', 'catalog:read');
        $product = ProductTestFactory::new()->withUnitAmountInCents(2_999)->create();
        $this->store($product);

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
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity, 'catalog:write');
        $product = ProductTestFactory::new()->create();
        $this->store($product);

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
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity, 'catalog:write');

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
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity, 'catalog:read');
        $product = ProductTestFactory::new()->create();
        $this->store($product);

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
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity, 'catalog:write', 'catalog:read');
        $product = ProductTestFactory::new()->create();
        $this->store($product);

        // When
        $client->request('POST', \sprintf('/v1/catalog/products/%s/delist', $product->id()->toString()));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->request('GET', \sprintf('/v1/catalog/products/%s', $product->id()->toString()));
        self::assertJsonContains(['delisted' => true]);
    }

    #[Test]
    public function itFailsToDelistAnUnknownProduct(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity, 'catalog:write');

        // When
        $client->request('POST', \sprintf('/v1/catalog/products/%s/delist', Uuid::uuid7()->toString()));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    #[Test]
    public function itRejectsADelistingOnAProductAlreadyDelisted(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity, 'catalog:write');
        $product = ProductTestFactory::new()->delisted()->create();
        $this->store($product);

        // When
        $client->request('POST', \sprintf('/v1/catalog/products/%s/delist', $product->id()->toString()));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    #[Test]
    public function itRejectsADelistingWithoutTheWriteGrant(): void
    {
        // Given
        $identity = IdentityTestFactory::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity, 'catalog:read');
        $product = ProductTestFactory::new()->create();
        $this->store($product);

        // When
        $client->request('POST', \sprintf('/v1/catalog/products/%s/delist', $product->id()->toString()));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
