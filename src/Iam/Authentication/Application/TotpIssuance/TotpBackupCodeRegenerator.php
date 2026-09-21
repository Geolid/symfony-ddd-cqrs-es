<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\TotpIssuance;

use Iam\Authentication\Application\Command\RegenerateBackupCodes\RegenerateBackupCodes;
use Iam\Authentication\Application\Finder\TotpCredential\TotpCredentialFinderInterface;
use Iam\Authentication\Application\TotpIssuance\Exception\TotpNotIssuedException;
use Iam\Authentication\Domain\TotpCredential\Service\TotpBackupCodeGeneratorInterface;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;

final readonly class TotpBackupCodeRegenerator implements TotpBackupCodeRegeneratorInterface
{
    public function __construct(
        private TotpCredentialFinderInterface $totpCredentialFinder,
        private TotpBackupCodeGeneratorInterface $backupCodeGenerator,
        private CommandBusInterface $commandBus,
        private int $backupCodeCount,
    ) {
    }

    /**
     * @return list<non-empty-string>
     *
     * @throws TotpNotIssuedException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function regenerateFor(string $identityId): array
    {
        $credential = $this->totpCredentialFinder->activeOfIdentityOrNull($identityId)
            ?? throw TotpNotIssuedException::forIdentity($identityId);

        $backupCodes = $this->backupCodeGenerator->generate($this->backupCodeCount);

        $this->commandBus->dispatch(new RegenerateBackupCodes($credential->id, $identityId, $backupCodes));

        return $backupCodes;
    }
}
