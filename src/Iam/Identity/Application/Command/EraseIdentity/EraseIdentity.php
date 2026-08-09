<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\EraseIdentity;

use Shared\Application\Command\CommandInterface;

final readonly class EraseIdentity implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
