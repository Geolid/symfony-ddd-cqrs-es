<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\ReactivateIdentity;

use Shared\Application\Command\CommandInterface;

final readonly class ReactivateIdentity implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
