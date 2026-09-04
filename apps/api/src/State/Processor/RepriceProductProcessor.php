<?php

declare(strict_types=1);

namespace Api\State\Processor;

use Api\Input\RepriceProductInput;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Catalog\Listing\Application\Command\RepriceProduct\RepriceProduct;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

/**
 * @implements ProcessorInterface<RepriceProductInput, void>
 */
final readonly class RepriceProductProcessor implements ProcessorInterface
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        \assert(null !== $data->unitPriceInCents);
        \assert(\is_string($uriVariables['id']));

        $this->commandBus->dispatch(new RepriceProduct($uriVariables['id'], $data->unitPriceInCents));
    }
}
