<?php

declare(strict_types=1);

namespace Support\Helpers;

use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Command\CommandInterface;
use Shared\Application\Query\QueryBusInterface;
use Shared\Application\Query\QueryInterface;

trait CqrsTrait
{
    abstract protected function service(string $serviceId): mixed;

    protected function dispatch(CommandInterface $command): void
    {
        $this->service(CommandBusInterface::class)->dispatch($command);
    }

    /**
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
