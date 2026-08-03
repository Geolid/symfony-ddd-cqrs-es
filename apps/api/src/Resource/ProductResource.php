<?php

declare(strict_types=1);

namespace Api\Resource;

use Api\State\Provider\ProductCollectionProvider;
use Api\State\Provider\ProductProvider;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Response;
use Catalog\Product\Application\Finder\Product\ProductResult;

#[ApiResource(
    shortName: 'Product',
    routePrefix: '/v1/catalog',
    operations: [
        new GetCollection(
            security: "is_granted('catalog:read')",
            openapi: new Operation(
                responses: ['200' => new Response(description: 'A collection of products.')],
                summary: 'Retrieves a collection of products.',
            ),
            provider: ProductCollectionProvider::class,
            parameters: [
                'includeDelisted' => new QueryParameter(
                    schema: ['type' => 'boolean'],
                    description: 'Whether delisted products are included in the collection.',
                ),
            ],
        ),
        new Get(
            uriTemplate: '/products/{id}',
            security: "is_granted('catalog:read')",
            openapi: new Operation(
                responses: [
                    '200' => new Response(description: 'The product.'),
                    '404' => new Response(description: 'No product carries that identifier.'),
                ],
                summary: 'Retrieves a single product.',
            ),
            provider: ProductProvider::class,
        ),
    ],
)]
final class ProductResource
{
    public function __construct(
        #[ApiProperty(description: 'The identifier of the product.', example: '0193c5f4-6a2e-7d18-b2c7-6f9e0a4d8b31')]
        public ?string $id = null,
        #[ApiProperty(description: 'The label of the product.', example: 'Wireless mouse')]
        public ?string $label = null,
        #[ApiProperty(description: 'The unit price of the product, in cents.', example: 2_999)]
        public ?int $unitAmountInCents = null,
        #[ApiProperty(description: 'Whether the product has been delisted.', example: false)]
        public ?bool $delisted = null,
    ) {
    }

    public static function fromResult(ProductResult $result): self
    {
        return new self(
            id: $result->id,
            label: $result->label,
            unitAmountInCents: $result->unitAmountInCents,
            delisted: $result->delisted,
        );
    }
}
