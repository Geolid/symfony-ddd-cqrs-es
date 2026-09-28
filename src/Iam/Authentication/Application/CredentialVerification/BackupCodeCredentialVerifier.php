<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\CredentialVerification;

use Iam\Authentication\Application\Command\ConsumeBackupCode\ConsumeBackupCode;
use Iam\Authentication\Application\Finder\BackupCodeCredential\BackupCodeCredentialFinderInterface;
use Iam\Authentication\Domain\BackupCodeCredential\Exception\InvalidBackupCodeException;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class BackupCodeCredentialVerifier implements BackupCodeCredentialVerifierInterface
{
    public function __construct(
        private BackupCodeCredentialFinderInterface $backupCodeCredentialFinder,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws \DomainException
     * @throws ApplicationExceptionInterface
     */
    public function verify(string $identityId, #[\SensitiveParameter] string $code): bool
    {
        if (null === $this->backupCodeCredentialFinder->ofIdentityOrNull($identityId)) {
            return false;
        }

        try {
            $this->commandBus->dispatch(new ConsumeBackupCode($identityId, $code));

            return true;
        } catch (InvalidBackupCodeException) {
            return false;
        }
    }
}
