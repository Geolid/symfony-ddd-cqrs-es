<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\ConsumeBackupCode;

use Iam\Authentication\Domain\TotpCredential\Exception\InvalidBackupCodeException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialAlreadyExistsException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialNotFoundException;
use Iam\Authentication\Domain\TotpCredential\Repository\TotpCredentialRepositoryInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpBackupCodeHasherInterface;
use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class ConsumeBackupCodeHandler
{
    public function __construct(
        private TotpCredentialRepositoryInterface $repository,
        private TotpBackupCodeHasherInterface $backupCodeHasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws TotpCredentialNotFoundException
     * @throws InvalidBackupCodeException
     * @throws TotpCredentialAlreadyExistsException
     */
    public function __invoke(ConsumeBackupCode $command): void
    {
        $credential = $this->repository->load(TotpCredentialId::fromString($command->id));
        $credential->consumeBackupCode($command->code, $this->backupCodeHasher, $this->clock->now());

        $this->repository->save($credential);
    }
}
