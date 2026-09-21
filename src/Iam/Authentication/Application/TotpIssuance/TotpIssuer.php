<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\TotpIssuance;

use Iam\Authentication\Application\Command\IssueTotpCredential\IssueTotpCredential;
use Iam\Authentication\Domain\TotpCredential\Exception\InvalidTotpCodeException;
use Iam\Authentication\Domain\TotpCredential\Service\TotpBackupCodeGeneratorInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpVerifierInterface;
use Ramsey\Uuid\Uuid;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class TotpIssuer implements TotpIssuerInterface
{
    public function __construct(
        private TotpVerifierInterface $verifier,
        private TotpBackupCodeGeneratorInterface $backupCodeGenerator,
        private CommandBusInterface $commandBus,
        private int $backupCodeCount,
    ) {
    }

    /**
     * @return list<non-empty-string>
     *
     * @throws InvalidTotpCodeException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function issueFor(string $identityId, #[\SensitiveParameter] string $secret, #[\SensitiveParameter] string $code): array
    {
        if (!$this->verifier->verify($secret, $code)) {
            throw InvalidTotpCodeException::forIdentity($identityId);
        }

        $backupCodes = $this->backupCodeGenerator->generate($this->backupCodeCount);

        $this->commandBus->dispatch(new IssueTotpCredential(Uuid::uuid7()->toString(), $identityId, $secret, $backupCodes));

        return $backupCodes;
    }
}
