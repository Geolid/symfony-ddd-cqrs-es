<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Credential\ApiKey;

use Iam\Authentication\Application\Exception\ApiKeyCredentialResultNotFoundException;
use Iam\Authentication\Application\Exception\ApiKeyCredentialRevokedException;
use Iam\Authentication\Application\Exception\IdentityNotAuthenticatableException;
use Shared\Application\DrivingPort;

#[DrivingPort]
interface ApiKeyCredentialVerifierInterface
{
    /**
     * @throws ApiKeyCredentialResultNotFoundException
     * @throws ApiKeyCredentialRevokedException
     * @throws IdentityNotAuthenticatableException
     */
    public function verify(string $keyId, #[\SensitiveParameter] string $secret): bool;
}
