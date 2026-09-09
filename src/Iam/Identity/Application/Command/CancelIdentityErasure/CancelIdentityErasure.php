<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\CancelIdentityErasure;

use Shared\Application\Command\CommandInterface;

final readonly class CancelIdentityErasure implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
