<?php

declare(strict_types=1);

namespace Api\Resource;

use Api\Input\PlaceOrderInput;
use Api\State\Processor\CancelOrderProcessor;
use Api\State\Processor\PlaceOrderProcessor;
use Api\State\Provider\OrderCollectionProvider;
use Api\State\Provider\OrderProvider;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Ordering\Order\Application\Finder\Order\OrderResult;

#[ApiResource(
    shortName: 'Order',
    routePrefix: '/v1/ordering',
    operations: [
        new GetCollection(provider: OrderCollectionProvider::class),
        new Get(uriTemplate: '/orders/{id}', provider: OrderProvider::class),
        new Post(
            uriTemplate: '/orders',
            status: 201,
            input: PlaceOrderInput::class,
            processor: PlaceOrderProcessor::class,
        ),
        new Post(
            uriTemplate: '/orders/{id}/cancel',
            status: 204,
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
        public ?string $id = null,
        public ?string $customerId = null,
        public ?int $totalAmountInCents = null,
        public ?string $status = null,
        public ?\DateTimeImmutable $placedAt = null,
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
