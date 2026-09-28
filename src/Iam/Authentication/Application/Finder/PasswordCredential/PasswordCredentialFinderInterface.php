<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\PasswordCredential;

interface PasswordCredentialFinderInterface
{
    public function ofIdentityOrNull(string $identityId): ?PasswordCredentialResult;
}
