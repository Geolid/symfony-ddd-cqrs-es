<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Credential;

use Iam\Authentication\Application\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Exception\PasswordCredentialResultNotFoundException;
use Shared\Application\Port\AsDrivingPort;

#[AsDrivingPort]
interface PasswordCredentialVerifierInterface
{
    /**
     * @throws PasswordCredentialResultNotFoundException
     * @throws IdentityNotAuthenticatableException
     */
    public function verify(string $identityId, #[\SensitiveParameter] string $plainPassword): bool;
}
