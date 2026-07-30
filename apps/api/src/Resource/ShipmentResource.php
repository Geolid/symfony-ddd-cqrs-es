<?php

declare(strict_types=1);

namespace Api\Resource;

use Api\State\Processor\DispatchShipmentProcessor;
use Api\State\Provider\ShipmentCollectionProvider;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Shipping\Shipment\Application\Finder\Shipment\ShipmentResult;
use Shipping\Shipment\Application\Language\PublishedShipmentStatus;
use Shipping\Shipment\Application\Validation\ValidShipmentStatus;

#[ApiResource(
    shortName: 'Shipment',
    routePrefix: '/v1/shipping',
    operations: [
        new GetCollection(
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
        public ?string $id = null,
        public ?string $orderId = null,
        public ?string $customerId = null,
        public ?int $orderTotalInCents = null,
        public ?string $status = null,
        public ?\DateTimeImmutable $createdAt = null,
        public ?\DateTimeImmutable $dispatchedAt = null,
        public ?\DateTimeImmutable $deliveredAt = null,
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
            createdAt: $result->createdAt,
            dispatchedAt: $result->dispatchedAt,
            deliveredAt: $result->deliveredAt,
            orderCancelledAt: $result->orderCancelledAt,
        );
    }
}
