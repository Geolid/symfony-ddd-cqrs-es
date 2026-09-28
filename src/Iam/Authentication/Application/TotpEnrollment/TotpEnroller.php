<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\TotpEnrollment;

use Iam\Authentication\Application\Command\EnrollTotp\EnrollTotp;
use Iam\Authentication\Application\Command\GenerateBackupCodes\GenerateBackupCodes;
use Iam\Authentication\Application\Finder\BackupCodeCredential\BackupCodeCredentialFinderInterface;
use Iam\Authentication\Domain\BackupCodeCredential\Service\BackupCodeGeneratorInterface;
use Iam\Authentication\Domain\TotpCredential\Exception\InvalidTotpCodeException;
use Iam\Authentication\Domain\TotpCredential\Service\TotpVerifierInterface;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class TotpEnroller implements TotpEnrollerInterface
{
    public function __construct(
        private TotpVerifierInterface $verifier,
        private BackupCodeCredentialFinderInterface $backupCodeCredentialFinder,
        private BackupCodeGeneratorInterface $backupCodeGenerator,
        private CommandBusInterface $commandBus,
        private int $backupCodeCount,
    ) {
    }

    /**
     * Backup codes are shown only the first time they're generated — enabling a second 2FA
     * method later reuses the identity's existing pool instead of silently replacing it.
     *
     * @return list<non-empty-string>|null
     *
     * @throws InvalidTotpCodeException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function enrollFor(string $identityId, #[\SensitiveParameter] string $secret, #[\SensitiveParameter] string $code): ?array
    {
        if (!$this->verifier->verify($secret, $code)) {
            throw InvalidTotpCodeException::forIdentity($identityId);
        }

        $this->commandBus->dispatch(new EnrollTotp(Uuid::uuid7()->toString(), $identityId, $secret));

        if (null !== $this->backupCodeCredentialFinder->ofIdentityOrNull($identityId)) {
            return null;
        }

        $backupCodes = $this->backupCodeGenerator->generate($this->backupCodeCount);

        $this->commandBus->dispatch(new GenerateBackupCodes($identityId, $backupCodes));

        return $backupCodes;
    }
}
