<?php

declare(strict_types=1);

namespace Support\TestCase;

use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Command\CommandInterface;
use Shared\Application\Query\QueryBusInterface;
use Shared\Application\Query\QueryInterface;

trait CqrsTrait
{
    /**
     * @template T of object
     *
     * @param class-string<T> $serviceId
     *
     * @return T
     */
    abstract protected function service(string $serviceId): object;

    /**
     * Dispatches a command through the command bus.
     */
    protected function dispatch(CommandInterface $command): void
    {
        $this->service(CommandBusInterface::class)->dispatch($command);
    }

    /**
     * Executes a query through the query bus.
     *
     * @template T
     *
     * @param QueryInterface<T> $query
     *
     * @return T
     */
    protected function ask(QueryInterface $query): mixed
    {
        return $this->service(QueryBusInterface::class)->ask($query);
    }
}
