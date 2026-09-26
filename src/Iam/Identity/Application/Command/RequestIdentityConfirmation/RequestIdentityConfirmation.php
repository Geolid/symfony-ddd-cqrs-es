<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RequestIdentityConfirmation;

use Shared\Application\Command\CommandInterface;

final readonly class RequestIdentityConfirmation implements CommandInterface
{
    public function __construct(public string $id)
    {
    }
}
