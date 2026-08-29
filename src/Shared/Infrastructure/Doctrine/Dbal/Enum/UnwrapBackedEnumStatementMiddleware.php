<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Doctrine\Dbal\Enum;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\ParameterType;

final class UnwrapBackedEnumStatementMiddleware extends AbstractStatementMiddleware
{
    public function bindValue(int|string $param, mixed $value, ParameterType $type): void
    {
        parent::bindValue($param, $value instanceof \BackedEnum ? $value->value : $value, $type);
    }
}
