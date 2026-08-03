<?php

declare(strict_types=1);

namespace Api\Resource;

use Api\State\Processor\DispatchShipmentProcessor;
use Api\State\Provider\ShipmentCollectionProvider;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Response;
use Fulfilment\Shipment\Application\Finder\Shipment\ShipmentResult;
use Fulfilment\Shipment\Application\Language\PublishedShipmentStatus;
use Fulfilment\Shipment\Application\Validation\ValidShipmentStatus;

#[ApiResource(
    shortName: 'Shipment',
    routePrefix: '/v1/fulfilment',
    operations: [
        new GetCollection(
            security: "is_granted('fulfilment:read')",
            openapi: new Operation(
                responses: [
                    '200' => new Response(description: 'A collection of shipments.'),
                    '422' => new Response(description: 'The status filter is not part of the shipment vocabulary.'),
                ],
                summary: 'Retrieves a collection of shipments.',
            ),
            provider: ShipmentCollectionProvider::class,
            parameters: [
                'status' => new QueryParameter(
                    constraints: [new ValidShipmentStatus()],
                    schema: ['type' => 'string', 'enum' => [
                        PublishedShipmentStatus::PENDING->value,
                        PublishedShipmentStatus::DISPATCHED->value,
                        PublishedShipmentStatus::DELIVERED->value,
                    ]],
                    description: 'Filter by shipment status.',
                ),
            ],
        ),
        new Post(
            uriTemplate: '/shipments/{id}/dispatch',
            status: 204,
            security: "is_granted('fulfilment:write')",
            openapi: new Operation(
                responses: [
                    '204' => new Response(description: 'The shipment was handed to the carrier.'),
                    '404' => new Response(description: 'No shipment carries that identifier.'),
                    '409' => new Response(description: 'The shipment has already left the warehouse.'),
                ],
                summary: 'Hands a pending shipment to the carrier.',
            ),
            input: false,
            output: false,
            name: 'dispatch',
            processor: DispatchShipmentProcessor::class,
        ),
    ],
)]
final class ShipmentResource
{
    public function __construct(
        #[ApiProperty(description: 'The identifier of the shipment.', example: '0193c5f5-1a44-7d18-b2c7-6f9e0a4d8b31')]
        public ?string $id = null,
        #[ApiProperty(description: 'The identifier of the order this shipment fulfils.', example: '0193c5f4-9c2e-7a1b-8f3d-2e5a7c9b1d40')]
        public ?string $orderId = null,
        #[ApiProperty(description: 'The identifier of the customer the shipment is addressed to.', example: '0193c5f4-7b10-7c42-9a6e-4d8f1b3c5e72')]
        public ?string $customerId = null,
        #[ApiProperty(description: 'The total amount of the fulfilled order, in cents.', example: 3500)]
        public ?int $orderTotalInCents = null,
        #[ApiProperty(description: 'The current status of the shipment.', example: 'pending')]
        public ?string $status = null,
        #[ApiProperty(description: "The carrier's own tracking reference, once the pickup has been booked.", example: 'ACME-4Q7X2K9')]
        public ?string $trackingReference = null,
        #[ApiProperty(description: 'The date and time when the shipment was created.', example: '2026-01-14T09:35:00+00:00')]
        public ?\DateTimeImmutable $createdAt = null,
        #[ApiProperty(description: 'The date and time when the shipment was handed to the carrier, if it was.', example: '2026-01-15T08:05:00+00:00')]
        public ?\DateTimeImmutable $dispatchedAt = null,
        #[ApiProperty(description: 'The date and time when the shipment was delivered, if it was.', example: '2026-01-17T11:40:00+00:00')]
        public ?\DateTimeImmutable $deliveredAt = null,
        #[ApiProperty(description: 'The date and time when the fulfilled order was cancelled, if it was.', example: '2026-01-15T14:20:00+00:00')]
        public ?\DateTimeImmutable $orderCancelledAt = null,
    ) {
    }

    public static function fromResult(ShipmentResult $result): self
    {
        return new self(
            id: $result->id,
            orderId: $result->orderId,
            customerId: $result->customerId,
            orderTotalInCents: $result->orderTotalInCents,
            status: $result->status,
            trackingReference: $result->trackingReference,
            createdAt: $result->createdAt,
            dispatchedAt: $result->dispatchedAt,
            deliveredAt: $result->deliveredAt,
            orderCancelledAt: $result->orderCancelledAt,
        );
    }
}
