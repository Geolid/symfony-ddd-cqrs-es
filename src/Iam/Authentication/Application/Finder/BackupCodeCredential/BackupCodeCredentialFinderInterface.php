<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\BackupCodeCredential;

interface BackupCodeCredentialFinderInterface
{
    public function ofIdentityOrNull(string $identityId): ?BackupCodeCredentialResult;
}
