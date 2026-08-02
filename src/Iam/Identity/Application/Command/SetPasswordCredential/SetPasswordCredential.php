<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\SetPasswordCredential;

use Shared\Application\Command\CommandInterface;

final readonly class SetPasswordCredential implements CommandInterface
{
    public function __construct(
        public string $identityId,
        public string $login,
        public string $password,
    ) {
    }
}
