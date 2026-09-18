<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\EraseUnconfirmedIdentity;

use Shared\Application\Command\CommandInterface;

final readonly class EraseUnconfirmedIdentity implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
