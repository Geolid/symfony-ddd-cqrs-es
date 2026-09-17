<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\RequestPasswordReset;

use Shared\Application\Command\CommandInterface;

final readonly class RequestPasswordReset implements CommandInterface
{
    public function __construct(public string $identityId)
    {
    }
}
