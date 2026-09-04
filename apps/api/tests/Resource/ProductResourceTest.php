<?php

declare(strict_types=1);

namespace Api\Tests\Resource;

use Api\Resource\ProductResource;
use Api\Tests\Support\AbstractApiTestCase;
use Catalog\Tests\Listing\Support\Builder\ProductBuilder;
use Iam\Tests\Identity\Support\Builder\IdentityBuilder;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

final class ProductResourceTest extends AbstractApiTestCase
{
    #[Test]
    public function itReturnsAProduct(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $product = ProductBuilder::new()->withLabel('Wireless mouse')->withUnitPriceInCents(2_999)->create();
        $this->store($identity, $product);
        $client = $this->authenticatedClient($identity);

        // When
        $client->request('GET', \sprintf('/v1/catalog/products/%s', $product->id->toString()));

        // Then
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertMatchesResourceItemJsonSchema(ProductResource::class);
        self::assertJsonContains([
            'id' => $product->id->toString(),
            'label' => 'Wireless mouse',
            'unitPriceInCents' => 2_999,
        ]);
    }

    #[Test]
    public function itFailsToReturnAnUnknownProduct(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity);

        // When
        $client->request('GET', '/v1/catalog/products/'.Uuid::uuid7()->toString());

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    #[Test]
    public function itReturnsTheProducts(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $delisted = ProductBuilder::new()->withLabel('Delisted keyboard')->delisted()->create();
        $product = ProductBuilder::new()->withLabel('Wireless mouse')->create();
        $this->store($identity, $delisted, $product);
        $client = $this->authenticatedClient($identity);

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
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);
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
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);
        $client = $this->revokedApiKeyClient($identity);

        // When
        $client->request('GET', '/v1/catalog/products');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function itRejectsASuspendedIdentity(): void
    {
        // Given
        $identity = IdentityBuilder::new()->suspended()->create();
        $client = $this->authenticatedClient($identity);
        $this->store($identity);

        // When
        $client->request('GET', '/v1/catalog/products');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function itAcceptsAProductToListForSale(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity);

        // When
        $response = $client->request('POST', '/v1/catalog/products', [
            'json' => ['label' => 'Wireless mouse', 'unitPriceInCents' => 2_999],
        ]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertMatchesResourceItemJsonSchema(ProductResource::class);
        self::assertJsonContains([
            'label' => 'Wireless mouse',
            'unitPriceInCents' => 2_999,
        ]);
        $content = $response->toArray();
        self::assertNotEmpty($content['id']);
    }

    #[Test]
    public function itFailsToAcceptANegativeUnitAmount(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity);

        // When
        $client->request('POST', '/v1/catalog/products', [
            'json' => ['label' => 'Wireless mouse', 'unitPriceInCents' => -1],
        ]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Test]
    public function itFailsToAcceptABlankLabel(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity);

        // When
        $client->request('POST', '/v1/catalog/products', [
            'json' => ['label' => '  ', 'unitPriceInCents' => 2_999],
        ]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Test]
    public function itAcceptsAReprice(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $product = ProductBuilder::new()->withUnitPriceInCents(2_999)->create();
        $this->store($identity, $product);
        $client = $this->authenticatedClient($identity);

        // When
        $client->request('POST', \sprintf('/v1/catalog/products/%s/reprice', $product->id->toString()), [
            'json' => ['unitPriceInCents' => 3_499],
        ]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->request('GET', \sprintf('/v1/catalog/products/%s', $product->id->toString()));
        self::assertJsonContains(['unitPriceInCents' => 3_499]);
    }

    #[Test]
    public function itFailsToAcceptANegativeReprice(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $product = ProductBuilder::new()->create();
        $this->store($identity, $product);
        $client = $this->authenticatedClient($identity);

        // When
        $client->request('POST', \sprintf('/v1/catalog/products/%s/reprice', $product->id->toString()), [
            'json' => ['unitPriceInCents' => -1],
        ]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[Test]
    public function itFailsToRepriceAnUnknownProduct(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity);

        // When
        $client->request('POST', \sprintf('/v1/catalog/products/%s/reprice', Uuid::uuid7()->toString()), [
            'json' => ['unitPriceInCents' => 3_499],
        ]);

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    #[Test]
    public function itAcceptsADelisting(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $product = ProductBuilder::new()->create();
        $this->store($identity, $product);
        $client = $this->authenticatedClient($identity);

        // When
        $client->request('POST', \sprintf('/v1/catalog/products/%s/delist', $product->id->toString()));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->request('GET', \sprintf('/v1/catalog/products/%s', $product->id->toString()));
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    #[Test]
    public function itFailsToDelistAnUnknownProduct(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $this->store($identity);
        $client = $this->authenticatedClient($identity);

        // When
        $client->request('POST', \sprintf('/v1/catalog/products/%s/delist', Uuid::uuid7()->toString()));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    #[Test]
    public function itAcceptsADelistingOnAProductAlreadyDelisted(): void
    {
        // Given
        $identity = IdentityBuilder::new()->create();
        $product = ProductBuilder::new()->delisted()->create();
        $this->store($identity, $product);
        $client = $this->authenticatedClient($identity);

        // When
        $client->request('POST', \sprintf('/v1/catalog/products/%s/delist', $product->id->toString()));

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }
}
