<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\ReleasePasswordCredentialLogin;

use Shared\Application\Command\CommandInterface;

final readonly class ReleasePasswordCredentialLogin implements CommandInterface
{
    public function __construct(public string $identityId)
    {
    }
}
