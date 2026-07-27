<?php

declare(strict_types=1);

namespace Api\State\Order;

use Api\Input\PlaceOrderInput;
use Api\Resource\OrderResource;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Ordering\Order\Application\Command\PlaceOrder\PlaceOrder;
use Ordering\Order\Application\Query\GetOrder\GetOrder;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Query\QueryBusInterface;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<PlaceOrderInput, OrderResource>
 */
final readonly class PlaceOrderProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): OrderResource
    {
        Assert::isInstanceOf($data, PlaceOrderInput::class);
        Assert::stringNotEmpty($data->customerId);
        Assert::natural($data->totalAmountInCents);

        $id = Uuid::uuid7()->toString();
        $this->commandBus->dispatch(new PlaceOrder($id, $data->customerId, $data->totalAmountInCents));

        return OrderResource::fromResult($this->queryBus->ask(new GetOrder($id)));
    }
}
