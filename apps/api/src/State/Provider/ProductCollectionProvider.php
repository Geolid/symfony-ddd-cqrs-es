<?php

declare(strict_types=1);

namespace Api\State\Provider;

use Api\Resource\ProductResource;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use Catalog\Product\Application\Query\ListProducts\ListProducts;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;

/**
 * @implements ProviderInterface<ProductResource>
 */
final readonly class ProductCollectionProvider implements ProviderInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private Pagination $pagination,
    ) {
    }

    /**
     * @return TraversablePaginator<ProductResource>
     *
     * @throws ApplicationExceptionInterface
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TraversablePaginator
    {
        $result = $this->queryBus->ask(new ListProducts(
            page: (int) $this->pagination->getPage($context),
            itemsPerPage: (int) $this->pagination->getLimit($operation, $context),
        ));

        return new TraversablePaginator(
            new \ArrayIterator(array_map(ProductResource::fromResult(...), $result->items)),
            (float) $result->pagination->currentPage,
            (float) $result->pagination->itemsPerPage,
            (float) $result->pagination->totalItems,
        );
    }
}
