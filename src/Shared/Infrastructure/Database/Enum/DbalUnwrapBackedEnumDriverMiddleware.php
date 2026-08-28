<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Database\Enum;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

final class DbalUnwrapBackedEnumDriverMiddleware extends AbstractDriverMiddleware
{
    public function connect(#[\SensitiveParameter] array $params): Connection
    {
        return new DbalUnwrapBackedEnumConnectionMiddleware(parent::connect($params));
    }
}
