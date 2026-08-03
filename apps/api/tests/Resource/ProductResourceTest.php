<?php

declare(strict_types=1);

namespace Api\Tests\Resource;

use Api\Resource\ProductResource;
use Api\Tests\Support\AbstractApiTestCase;
use Catalog\Tests\Product\Support\Factory\ProductTestFactory;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

final class ProductResourceTest extends AbstractApiTestCase
{
    #[Test]
    public function itReturnsAProduct(): void
    {
        // Given
        $client = $this->authenticatedClient('catalog:read');
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
        $client = $this->authenticatedClient('catalog:read');

        // When
        $client->request('GET', '/v1/catalog/products/'.Uuid::uuid7()->toString());

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    #[Test]
    public function itReturnsTheProducts(): void
    {
        // Given
        $client = $this->authenticatedClient('catalog:read');
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
        $client = $this->authenticatedClient('catalog:read');
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
        $client = self::jsonClient();

        // When
        $client->request('GET', '/v1/catalog/products');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function itRejectsACallerWithoutTheReadGrant(): void
    {
        // Given
        $client = $this->authenticatedClient();

        // When
        $client->request('GET', '/v1/catalog/products');

        // Then
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
