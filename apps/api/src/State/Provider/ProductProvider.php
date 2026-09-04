<?php

declare(strict_types=1);

namespace Api\State\Provider;

use Api\Resource\ProductResource;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Catalog\Listing\Application\Exception\ProductResultNotFoundException;
use Catalog\Listing\Application\Query\GetProduct\GetProduct;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;

/**
 * @implements ProviderInterface<ProductResource>
 */
final readonly class ProductProvider implements ProviderInterface
{
    public function __construct(private QueryBusInterface $queryBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?ProductResource
    {
        \assert(\is_string($uriVariables['id']));

        try {
            $result = $this->queryBus->ask(new GetProduct($uriVariables['id']));
        } catch (ProductResultNotFoundException) {
            return null;
        }

        return ProductResource::fromResult($result);
    }
}
