<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Security;

use Shared\Application\Port\AsDrivingPort;

#[AsDrivingPort]
interface PasswordCredentialAuthenticatorInterface
{
    /**
     * @return string|null the authenticated identity's id, or null when the login/password pair is refused
     */
    public function authenticate(string $login, string $plainPassword): ?string;
}
