<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\ErasePendingIdentity;

use Shared\Application\Command\CommandInterface;

final readonly class ErasePendingIdentity implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
