<?php

declare(strict_types=1);

namespace Api\State\Processor;

use Api\Input\PublishProductInput;
use Api\Resource\ProductResource;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Catalog\Listing\Application\Command\PublishProduct\PublishProduct;
use Catalog\Listing\Application\Query\GetProduct\GetProduct;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;

/**
 * @implements ProcessorInterface<PublishProductInput, ProductResource>
 */
final readonly class PublishProductProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ProductResource
    {
        \assert(null !== $data->label);
        \assert(null !== $data->unitPriceInCents);

        $id = Uuid::uuid7()->toString();
        $this->commandBus->dispatch(new PublishProduct($id, $data->label, $data->unitPriceInCents));

        return ProductResource::fromResult($this->queryBus->ask(new GetProduct($id)));
    }
}
