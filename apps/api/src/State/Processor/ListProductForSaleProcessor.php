<?php

declare(strict_types=1);

namespace Api\State\Processor;

use Api\Input\ListProductForSaleInput;
use Api\Resource\ProductResource;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Catalog\Product\Application\Command\ListProductForSale\ListProductForSale;
use Catalog\Product\Application\Query\GetProduct\GetProduct;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Query\QueryBusInterface;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<ListProductForSaleInput, ProductResource>
 */
final readonly class ListProductForSaleProcessor implements ProcessorInterface
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
        Assert::isInstanceOf($data, ListProductForSaleInput::class);
        Assert::stringNotEmpty($data->label);
        Assert::integer($data->unitAmountInCents);

        $id = Uuid::uuid7()->toString();
        $this->commandBus->dispatch(new ListProductForSale($id, $data->label, $data->unitAmountInCents));

        return ProductResource::fromResult($this->queryBus->ask(new GetProduct($id)));
    }
}
