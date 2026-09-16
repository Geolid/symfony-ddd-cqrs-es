<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\ConfirmTotpEnrollment;

use Iam\Authentication\Domain\TotpCredential\Exception\InvalidTotpCodeException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialAlreadyExistsException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialNotConfirmableException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialNotFoundException;
use Iam\Authentication\Domain\TotpCredential\Exception\TotpCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\TotpCredential\Repository\TotpCredentialRepositoryInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpCipherInterface;
use Iam\Authentication\Domain\TotpCredential\Service\TotpVerifierInterface;
use Iam\Authentication\Domain\TotpCredential\ValueObject\TotpCredentialId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;

#[CommandHandler]
final readonly class ConfirmTotpEnrollmentHandler
{
    public function __construct(
        private TotpCredentialRepositoryInterface $repository,
        private TotpCipherInterface $cipher,
        private TotpVerifierInterface $verifier,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws TotpCredentialNotFoundException
     * @throws TotpCredentialOwnedByAnotherIdentityException
     * @throws TotpCredentialNotConfirmableException
     * @throws InvalidTotpCodeException
     * @throws TotpCredentialAlreadyExistsException
     */
    public function __invoke(ConfirmTotpEnrollment $command): void
    {
        $credential = $this->repository->load(TotpCredentialId::fromString($command->id));
        $credential->confirm($command->identityId, $command->code, $this->cipher, $this->verifier, $this->clock->now());

        $this->repository->save($credential);
    }
}
