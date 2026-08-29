<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Doctrine\Dbal\Enum;

use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Statement;

final class UnwrapBackedEnumConnectionMiddleware extends AbstractConnectionMiddleware
{
    public function prepare(string $sql): Statement
    {
        return new UnwrapBackedEnumStatementMiddleware(parent::prepare($sql));
    }
}
