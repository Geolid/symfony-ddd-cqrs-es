<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\RegenerateBackupCodes;

use Iam\Authentication\Domain\BackupCodeCredential\Exception\BackupCodeCredentialAlreadyExistsException;
use Iam\Authentication\Domain\BackupCodeCredential\Exception\BackupCodeCredentialNotFoundException;
use Iam\Authentication\Domain\BackupCodeCredential\Repository\BackupCodeCredentialRepositoryInterface;
use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeHasherInterface;
use Iam\Authentication\Domain\BackupCodeCredential\ValueObject\BackupCodeCredentialId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class RegenerateBackupCodesHandler
{
    public function __construct(
        private BackupCodeCredentialRepositoryInterface $repository,
        private BackupCodeHasherInterface $backupCodeHasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws BackupCodeCredentialNotFoundException
     * @throws BackupCodeCredentialAlreadyExistsException
     */
    public function __invoke(RegenerateBackupCodes $command): void
    {
        $credential = $this->repository->load(BackupCodeCredentialId::forIdentity($command->identityId));
        $credential->regenerate($command->backupCodes, $this->backupCodeHasher, $this->clock->now());

        $this->repository->save($credential);
    }
}
