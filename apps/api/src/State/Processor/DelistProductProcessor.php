<?php

declare(strict_types=1);

namespace Api\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Catalog\Product\Application\Command\DelistProduct\DelistProduct;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<null, void>
 */
final readonly class DelistProductProcessor implements ProcessorInterface
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
        Assert::string($uriVariables['id']);
        $this->commandBus->dispatch(new DelistProduct($uriVariables['id']));
    }
}
