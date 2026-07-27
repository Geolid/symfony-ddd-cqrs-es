<?php

declare(strict_types=1);

namespace Shared\Application\Query;

use Shared\Application\Attribute\AsDrivingPort;
use Shared\Application\Exception\ApplicationExceptionInterface;

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
