<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\IssueBackupCodeCredential;

use Iam\Authentication\Domain\BackupCodeCredential\BackupCodeCredential;
use Iam\Authentication\Domain\BackupCodeCredential\Exception\BackupCodeCredentialAlreadyExistsException;
use Iam\Authentication\Domain\BackupCodeCredential\Repository\BackupCodeCredentialRepositoryInterface;
use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Authentication\Domain\BackupCodeCredential\ValueObject\BackupCodeCredentialId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class IssueBackupCodeCredentialHandler
{
    public function __construct(
        private BackupCodeCredentialRepositoryInterface $repository,
        private BackupCodeHasherInterface $backupCodeHasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws BackupCodeCredentialAlreadyExistsException
     */
    public function __invoke(IssueBackupCodeCredential $command): void
    {
        $credential = BackupCodeCredential::issue(
            id: BackupCodeCredentialId::forIdentity($command->identityId),
            identityId: $command->identityId,
            plainBackupCodes: $command->backupCodes,
            backupCodeHasher: $this->backupCodeHasher,
            issuedAt: $this->clock->now(),
        );

        $this->repository->save($credential);
    }
}
