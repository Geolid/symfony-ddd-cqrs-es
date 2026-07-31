<?php

declare(strict_types=1);

namespace Api\Resource;

use Api\Input\PlaceOrderInput;
use Api\State\Processor\CancelOrderProcessor;
use Api\State\Processor\PlaceOrderProcessor;
use Api\State\Provider\OrderCollectionProvider;
use Api\State\Provider\OrderProvider;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Response;
use Sales\Order\Application\Finder\Order\OrderResult;

#[ApiResource(
    shortName: 'Order',
    routePrefix: '/v1/sales',
    operations: [
        new GetCollection(
            openapi: new Operation(
                responses: ['200' => new Response(description: 'A collection of orders.')],
                summary: 'Retrieves a collection of orders.',
            ),
            provider: OrderCollectionProvider::class,
        ),
        new Get(
            uriTemplate: '/orders/{id}',
            openapi: new Operation(
                responses: [
                    '200' => new Response(description: 'The order.'),
                    '404' => new Response(description: 'No order carries that identifier.'),
                ],
                summary: 'Retrieves a single order.',
            ),
            provider: OrderProvider::class,
        ),
        new Post(
            uriTemplate: '/orders',
            status: 201,
            openapi: new Operation(
                responses: [
                    '201' => new Response(description: 'The order that was placed.'),
                    '422' => new Response(description: 'The payload does not satisfy the order constraints.'),
                ],
                summary: 'Places an order.',
            ),
            input: PlaceOrderInput::class,
            processor: PlaceOrderProcessor::class,
        ),
        new Post(
            uriTemplate: '/orders/{id}/cancel',
            status: 204,
            openapi: new Operation(
                responses: [
                    '204' => new Response(description: 'The order was cancelled.'),
                    '404' => new Response(description: 'No order carries that identifier.'),
                    '409' => new Response(description: 'The order is already cancelled.'),
                ],
                summary: 'Cancels an order.',
            ),
            input: false,
            output: false,
            name: 'cancel',
            processor: CancelOrderProcessor::class,
        ),
    ],
)]
final class OrderResource
{
    public function __construct(
        #[ApiProperty(description: 'The identifier of the order.', example: '0193c5f4-9c2e-7a1b-8f3d-2e5a7c9b1d40')]
        public ?string $id = null,
        #[ApiProperty(description: 'The identifier of the customer who placed the order.', example: '0193c5f4-7b10-7c42-9a6e-4d8f1b3c5e72')]
        public ?string $customerId = null,
        #[ApiProperty(description: 'The total amount of the order, in cents.', example: 3500)]
        public ?int $totalAmountInCents = null,
        #[ApiProperty(description: 'The current status of the order.', example: 'placed')]
        public ?string $status = null,
        #[ApiProperty(description: 'The date and time when the order was placed.', example: '2026-01-14T09:30:00+00:00')]
        public ?\DateTimeImmutable $placedAt = null,
        #[ApiProperty(description: 'The date and time when the order was cancelled, if it was.', example: '2026-01-15T14:20:00+00:00')]
        public ?\DateTimeImmutable $cancelledAt = null,
    ) {
    }

    public static function fromResult(OrderResult $result): self
    {
        return new self(
            id: $result->id,
            customerId: $result->customerId,
            totalAmountInCents: $result->totalAmountInCents,
            status: $result->status,
            placedAt: $result->placedAt,
            cancelledAt: $result->cancelledAt,
        );
    }
}
