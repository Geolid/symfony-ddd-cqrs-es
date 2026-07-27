<?php

declare(strict_types=1);

namespace Api\State\Order;

use Api\Resource\OrderResource;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Ordering\Order\Application\Exception\OrderResultNotFoundException;
use Ordering\Order\Application\Query\GetOrder\GetOrder;
use Shared\Application\Query\QueryBusInterface;
use Webmozart\Assert\Assert;

/**
 * @implements ProviderInterface<OrderResource>
 */
final readonly class OrderProvider implements ProviderInterface
{
    public function __construct(private QueryBusInterface $queryBus)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?OrderResource
    {
        Assert::string($uriVariables['id']);

        try {
            $result = $this->queryBus->ask(new GetOrder($uriVariables['id']));
        } catch (OrderResultNotFoundException) {
            return null;
        }

        return OrderResource::fromResult($result);
    }
}
