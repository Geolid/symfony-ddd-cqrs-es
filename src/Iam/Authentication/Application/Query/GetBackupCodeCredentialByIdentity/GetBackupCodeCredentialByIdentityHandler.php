<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Query\GetBackupCodeCredentialByIdentity;

use Iam\Authentication\Application\Finder\BackupCodeCredential\BackupCodeCredentialFinderInterface;
use Iam\Authentication\Application\Finder\BackupCodeCredential\BackupCodeCredentialResult;
use Shared\Application\Query\QueryHandler;

#[QueryHandler]
final readonly class GetBackupCodeCredentialByIdentityHandler
{
    public function __construct(private BackupCodeCredentialFinderInterface $backupCodeCredentialFinder)
    {
    }

    public function __invoke(GetBackupCodeCredentialByIdentity $query): ?BackupCodeCredentialResult
    {
        return $this->backupCodeCredentialFinder->ofIdentityOrNull($query->identityId);
    }
}
