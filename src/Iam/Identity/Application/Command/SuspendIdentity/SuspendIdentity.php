<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\SuspendIdentity;

use Shared\Application\Command\CommandInterface;

final readonly class SuspendIdentity implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
