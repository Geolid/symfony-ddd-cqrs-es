<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Security;

use Iam\Identity\Application\Exception\PasswordCredentialAuthenticationFailedException;
use Shared\Application\Port\AsDrivingPort;

#[AsDrivingPort]
interface PasswordCredentialAuthenticatorInterface
{
    /**
     * @throws PasswordCredentialAuthenticationFailedException
     */
    public function authenticate(string $login, string $plainPassword): string;
}
