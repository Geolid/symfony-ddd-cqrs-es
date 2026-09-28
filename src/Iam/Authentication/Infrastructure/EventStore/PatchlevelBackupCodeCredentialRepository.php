<?php

declare(strict_types=1);

namespace Iam\Authentication\Infrastructure\EventStore;

use Iam\Authentication\Domain\BackupCodeCredential\BackupCodeCredential;
use Iam\Authentication\Domain\BackupCodeCredential\Exception\BackupCodeCredentialAlreadyExistsException;
use Iam\Authentication\Domain\BackupCodeCredential\Exception\BackupCodeCredentialNotFoundException;
use Iam\Authentication\Domain\BackupCodeCredential\Repository\BackupCodeCredentialRepositoryInterface;
use Iam\Authentication\Domain\BackupCodeCredential\ValueObject\BackupCodeCredentialId;
use Patchlevel\EventSourcing\Repository\AggregateAlreadyExists;
use Patchlevel\EventSourcing\Repository\AggregateNotFound;
use Patchlevel\EventSourcing\Repository\Repository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PatchlevelBackupCodeCredentialRepository implements BackupCodeCredentialRepositoryInterface
{
    /**
     * @param Repository<BackupCodeCredential> $repository
     */
    public function __construct(
        #[Autowire(service: 'event_sourcing.iam.authentication.backup_code_credential.repository')]
        private Repository $repository,
    ) {
    }

    public function has(BackupCodeCredentialId $id): bool
    {
        return $this->repository->has($id);
    }

    public function load(BackupCodeCredentialId $id): BackupCodeCredential
    {
        try {
            return $this->repository->load($id);
        } catch (AggregateNotFound) {
            throw BackupCodeCredentialNotFoundException::forId($id->toString());
        }
    }

    public function save(BackupCodeCredential $backupCodeCredential): void
    {
        try {
            $this->repository->save($backupCodeCredential);
        } catch (AggregateAlreadyExists) {
            throw BackupCodeCredentialAlreadyExistsException::forId($backupCodeCredential->id->toString());
        }
    }
}
