<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\BackupCodeRegeneration;

use Iam\Authentication\Application\BackupCodeRegeneration\Exception\BackupCodeCredentialNotGeneratedException;
use Iam\Authentication\Application\Command\RegenerateBackupCodes\RegenerateBackupCodes;
use Iam\Authentication\Application\Finder\BackupCodeCredential\BackupCodeCredentialFinderInterface;
use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeGeneratorInterface;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class BackupCodeRegenerator implements BackupCodeRegeneratorInterface
{
    public function __construct(
        private BackupCodeCredentialFinderInterface $backupCodeCredentialFinder,
        private BackupCodeGeneratorInterface $backupCodeGenerator,
        private CommandBusInterface $commandBus,
        private int $backupCodeCount,
    ) {
    }

    /**
     * @return list<non-empty-string>
     *
     * @throws BackupCodeCredentialNotGeneratedException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function regenerateFor(string $identityId): array
    {
        $this->backupCodeCredentialFinder->ofIdentityOrNull($identityId)
            ?? throw BackupCodeCredentialNotGeneratedException::forIdentity($identityId);

        $backupCodes = $this->backupCodeGenerator->generate($this->backupCodeCount);

        $this->commandBus->dispatch(new RegenerateBackupCodes($identityId, $backupCodes));

        return $backupCodes;
    }
}
