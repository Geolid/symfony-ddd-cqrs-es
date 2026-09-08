<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RequestIdentityErasure;

use Shared\Application\Command\CommandInterface;

final readonly class RequestIdentityErasure implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
