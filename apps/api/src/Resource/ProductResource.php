<?php

declare(strict_types=1);

namespace Api\Resource;

use Api\Input\ListProductForSaleInput;
use Api\Input\RepriceProductInput;
use Api\State\Processor\DelistProductProcessor;
use Api\State\Processor\ListProductForSaleProcessor;
use Api\State\Processor\RepriceProductProcessor;
use Api\State\Provider\ProductCollectionProvider;
use Api\State\Provider\ProductProvider;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
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
        new Post(
            uriTemplate: '/products',
            status: 201,
            security: "is_granted('catalog:write')",
            openapi: new Operation(
                responses: [
                    '201' => new Response(description: 'The product that was listed for sale.'),
                    '422' => new Response(description: 'The payload does not satisfy the product constraints.'),
                ],
                summary: 'Lists a new product for sale.',
            ),
            input: ListProductForSaleInput::class,
            processor: ListProductForSaleProcessor::class,
        ),
        new Post(
            uriTemplate: '/products/{id}/reprice',
            status: 204,
            security: "is_granted('catalog:write')",
            openapi: new Operation(
                responses: [
                    '204' => new Response(description: 'The product was repriced.'),
                    '404' => new Response(description: 'No product carries that identifier.'),
                ],
                summary: "Changes a product's unit price.",
            ),
            input: RepriceProductInput::class,
            output: false,
            name: 'reprice',
            processor: RepriceProductProcessor::class,
        ),
        new Post(
            uriTemplate: '/products/{id}/delist',
            status: 204,
            security: "is_granted('catalog:write')",
            openapi: new Operation(
                responses: [
                    '204' => new Response(description: 'The product was delisted.'),
                    '404' => new Response(description: 'No product carries that identifier.'),
                    '409' => new Response(description: 'The product is already delisted.'),
                ],
                summary: 'Delists a product.',
            ),
            input: false,
            output: false,
            name: 'delist',
            processor: DelistProductProcessor::class,
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
