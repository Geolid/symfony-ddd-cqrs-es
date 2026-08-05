<?php

declare(strict_types=1);

namespace Api\State\Provider;

use Api\Resource\OrderResource;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Sales\OrderSummary\Application\Query\GetOrderSummary\GetOrderSummary;
use Shared\Application\Exception\ApplicationExceptionInterface;
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

    /**
     * @throws ApplicationExceptionInterface
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?OrderResource
    {
        Assert::string($uriVariables['id']);

        $result = $this->queryBus->ask(new GetOrderSummary($uriVariables['id']));

        return null !== $result ? OrderResource::fromResult($result) : null;
    }
}
