<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Query\GetBackupCodeCredentialByIdentity;

use Iam\Authentication\Application\Finder\BackupCodeCredential\BackupCodeCredentialResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<?BackupCodeCredentialResult>
 */
final readonly class GetBackupCodeCredentialByIdentity implements QueryInterface
{
    public function __construct(public string $identityId)
    {
    }
}
