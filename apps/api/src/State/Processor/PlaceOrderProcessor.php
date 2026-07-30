<?php

declare(strict_types=1);

namespace Api\State\Processor;

use Api\Input\PlaceOrderInput;
use Api\Resource\OrderResource;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Ramsey\Uuid\Uuid;
use Sales\Order\Application\Command\PlaceOrder\PlaceOrder;
use Sales\Order\Application\Query\GetOrder\GetOrder;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
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

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
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
