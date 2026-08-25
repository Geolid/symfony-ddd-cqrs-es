<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\DefinePasswordCredential;

use Shared\Application\Command\CommandInterface;

final readonly class DefinePasswordCredential implements CommandInterface
{
    public function __construct(
        public string $identityId,
        public string $login,
        #[\SensitiveParameter]
        public string $password,
    ) {
    }
}
