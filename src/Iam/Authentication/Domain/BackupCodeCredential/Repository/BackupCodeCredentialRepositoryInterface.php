<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\BackupCodeCredential\Repository;

use Iam\Authentication\Domain\BackupCodeCredential\BackupCodeCredential;
use Iam\Authentication\Domain\BackupCodeCredential\Exception\BackupCodeCredentialAlreadyExistsException;
use Iam\Authentication\Domain\BackupCodeCredential\Exception\BackupCodeCredentialNotFoundException;
use Iam\Authentication\Domain\BackupCodeCredential\ValueObject\BackupCodeCredentialId;

interface BackupCodeCredentialRepositoryInterface
{
    public function has(BackupCodeCredentialId $id): bool;

    /**
     * @throws BackupCodeCredentialNotFoundException
     */
    public function load(BackupCodeCredentialId $id): BackupCodeCredential;

    /**
     * @throws BackupCodeCredentialAlreadyExistsException
     */
    public function save(BackupCodeCredential $backupCodeCredential): void;
}
