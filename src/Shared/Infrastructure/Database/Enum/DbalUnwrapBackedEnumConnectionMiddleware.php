<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Database\Enum;

use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Statement;

final class DbalUnwrapBackedEnumConnectionMiddleware extends AbstractConnectionMiddleware
{
    public function prepare(string $sql): Statement
    {
        return new DbalUnwrapBackedEnumStatementMiddleware(parent::prepare($sql));
    }
}
