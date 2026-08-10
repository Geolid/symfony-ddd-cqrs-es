<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\RevokeApiTokenCredentialsForIdentity;

use Shared\Application\Command\CommandInterface;

final readonly class RevokeApiTokenCredentialsForIdentity implements CommandInterface
{
    public function __construct(public string $identityId)
    {
    }
}
