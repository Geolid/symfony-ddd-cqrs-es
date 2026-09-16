<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\CredentialVerification;

use Iam\Authentication\Application\CredentialVerification\Exception\IdentityNotAuthenticatableException;
use Shared\Application\DrivingPort;

#[DrivingPort]
interface TotpCredentialVerifierInterface
{
    /**
     * @throws IdentityNotAuthenticatableException
     */
    public function verify(string $identityId, #[\SensitiveParameter] string $code): bool;
}
