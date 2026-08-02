<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Port;

use Shared\Application\Port\AsDrivingPort;

#[AsDrivingPort]
interface AuthenticatePasswordCredentialInterface
{
    /**
     * @return string|null the authenticated identity's id, or null when the login/password pair or the identity's status refuses authentication
     */
    public function authenticate(string $login, string $plainPassword): ?string;
}
