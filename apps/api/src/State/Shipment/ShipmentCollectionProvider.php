<?php

declare(strict_types=1);

namespace Api\State\Shipment;

use Api\Resource\ShipmentResource;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use Shared\Application\Query\QueryBusInterface;
use Shipping\Shipment\Application\Query\ListShipments\ListShipments;

/**
 * @implements ProviderInterface<ShipmentResource>
 */
final readonly class ShipmentCollectionProvider implements ProviderInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private Pagination $pagination,
    ) {
    }

    /**
     * @return TraversablePaginator<ShipmentResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TraversablePaginator
    {
        /** @var array{status?: string, ...} $filters */
        $filters = $context['filters'] ?? [];

        $result = $this->queryBus->ask(new ListShipments(
            status: $filters['status'] ?? null,
            page: (int) $this->pagination->getPage($context),
            itemsPerPage: (int) $this->pagination->getLimit($operation, $context),
        ));

        return new TraversablePaginator(
            new \ArrayIterator(array_map(ShipmentResource::fromResult(...), $result->items)),
            (float) $result->pagination->currentPage,
            (float) $result->pagination->itemsPerPage,
            (float) $result->pagination->totalItems,
        );
    }
}
