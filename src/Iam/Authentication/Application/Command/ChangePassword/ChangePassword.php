<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\ChangePassword;

use Shared\Application\Command\ActingIdentityAware;
use Shared\Application\Command\CommandInterface;

final readonly class ChangePassword implements CommandInterface, ActingIdentityAware
{
    public function __construct(
        public string $identityId,
        #[\SensitiveParameter]
        public string $password,
    ) {
    }

    public function actingIdentityId(): string
    {
        return $this->identityId;
    }
}
