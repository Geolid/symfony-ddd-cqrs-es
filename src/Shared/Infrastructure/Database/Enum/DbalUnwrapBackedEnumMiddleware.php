<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Database\Enum;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;

/**
 * Lets a `\BackedEnum` bind directly into a query — unwraps it to its own `->value` at
 * the Statement's `bindValue()`, so every call site can pass the enum as-is.
 */
final class DbalUnwrapBackedEnumMiddleware implements Middleware
{
    public function wrap(Driver $driver): Driver
    {
        return new DbalUnwrapBackedEnumDriverMiddleware($driver);
    }
}
