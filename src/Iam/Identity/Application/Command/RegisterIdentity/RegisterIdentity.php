<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RegisterIdentity;

use Shared\Application\Command\CommandInterface;

final readonly class RegisterIdentity implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
