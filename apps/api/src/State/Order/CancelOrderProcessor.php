<?php

declare(strict_types=1);

namespace Api\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Ordering\Order\Application\Command\CancelOrder\CancelOrder;
use Shared\Application\Command\CommandBusInterface;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<null, void>
 */
final readonly class CancelOrderProcessor implements ProcessorInterface
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        Assert::string($uriVariables['id']);
        $this->commandBus->dispatch(new CancelOrder($uriVariables['id']));
    }
}
