<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Doctrine\Dbal\Enum;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

final class UnwrapBackedEnumDriverMiddleware extends AbstractDriverMiddleware
{
    public function connect(#[\SensitiveParameter] array $params): Connection
    {
        return new UnwrapBackedEnumConnectionMiddleware(parent::connect($params));
    }
}
