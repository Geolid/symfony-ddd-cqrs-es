<?php

declare(strict_types=1);

namespace Shared\Application\Query;

use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Port\AsDrivingPort;

#[AsDrivingPort]
interface QueryBusInterface
{
    /**
     * @template TResult
     *
     * @param QueryInterface<TResult> $query
     *
     * @return TResult
     *
     * @throws ApplicationExceptionInterface
     */
    public function ask(QueryInterface $query): mixed;
}
