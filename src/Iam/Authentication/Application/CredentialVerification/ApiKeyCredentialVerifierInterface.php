<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\CredentialVerification;

use Iam\Authentication\Application\CredentialVerification\Exception\ApiKeyCredentialRevokedException;
use Iam\Authentication\Application\CredentialVerification\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\ApiKeyCredential\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Authentication\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Shared\Application\DrivingPort;

#[DrivingPort]
interface ApiKeyCredentialVerifierInterface
{
    /**
     * @throws ApiKeyCredentialResultNotFoundException
     * @throws ApiKeyCredentialRevokedException
     * @throws IdentityResultNotFoundException
     * @throws IdentityNotAuthenticatableException
     */
    public function verify(string $keyId, #[\SensitiveParameter] string $secret): bool;
}
