<?php

declare(strict_types=1);

namespace Iam\Access\Application\Command\RevokePermission;

use Shared\Application\Command\CommandInterface;

final readonly class RevokePermission implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
