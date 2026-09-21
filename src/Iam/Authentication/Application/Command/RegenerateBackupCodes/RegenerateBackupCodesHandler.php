<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\RegenerateBackupCodes;

use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialAlreadyExistsException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialNotFoundException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\TotpCredential\Repository\TotpCredentialRepositoryInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpBackupCodeHasherInterface;
use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class RegenerateBackupCodesHandler
{
    public function __construct(
        private TotpCredentialRepositoryInterface $repository,
        private TotpBackupCodeHasherInterface $backupCodeHasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws TotpCredentialNotFoundException
     * @throws TotpCredentialOwnedByAnotherIdentityException
     * @throws TotpCredentialAlreadyExistsException
     */
    public function __invoke(RegenerateBackupCodes $command): void
    {
        $credential = $this->repository->load(TotpCredentialId::fromString($command->id));
        $credential->regenerateBackupCodes(
            $command->identityId,
            $command->backupCodes,
            $this->backupCodeHasher,
            $this->clock->now(),
        );

        $this->repository->save($credential);
    }
}
