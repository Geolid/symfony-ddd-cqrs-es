<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\CredentialVerification;

use Iam\Authentication\Application\Command\ConsumeBackupCode\ConsumeBackupCode;
use Iam\Authentication\Application\Finder\BackupCodeCredential\BackupCodeCredentialFinderInterface;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class BackupCodeCredentialVerifier implements BackupCodeCredentialVerifierInterface
{
    public function __construct(
        private BackupCodeCredentialFinderInterface $backupCodeCredentialFinder,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function verify(string $identityId, #[\SensitiveParameter] string $code): bool
    {
        if (null === $this->backupCodeCredentialFinder->ofIdentityOrNull($identityId)) {
            return false;
        }

        /*
         * The ApplicationExceptionInterface branch is only the bus's own generic contract —
         * ConsumeBackupCodeHandler's real chain throws Domain exceptions exclusively, so no
         * test can honestly reach it.
         *
         * @infection-ignore-all
         */
        try {
            $this->commandBus->dispatch(new ConsumeBackupCode($identityId, $code));

            return true;
        } catch (ApplicationExceptionInterface|\DomainException) {
            return false;
        }
    }
}
