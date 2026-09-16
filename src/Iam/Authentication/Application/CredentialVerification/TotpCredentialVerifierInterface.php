<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\CredentialVerification;

use Iam\Authentication\Application\CredentialVerification\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\TotpCredential\Exception\TotpCredentialResultNotFoundException;
use Shared\Application\DrivingPort;

#[DrivingPort]
interface TotpCredentialVerifierInterface
{
    /**
     * @throws TotpCredentialResultNotFoundException
     * @throws IdentityNotAuthenticatableException
     */
    public function verify(string $identityId, #[\SensitiveParameter] string $code): bool;
}
