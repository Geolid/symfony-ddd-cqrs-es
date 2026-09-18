<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\ConfirmIdentityEmail;

use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityAlreadyExistsException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Exception\IdentityNotPendingException;
use Iam\Identity\Domain\Exception\InvalidConfirmationCodeException;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Domain\Exception\VerificationCodeAttemptsExceededException;
use Shared\Domain\Exception\VerificationCodeNotFoundException;
use Shared\Domain\Service\VerificationCodeInterface;

#[CommandHandler]
final readonly class ConfirmIdentityEmailHandler
{
    public function __construct(
        private IdentityRepositoryInterface $repository,
        private VerificationCodeInterface $verifier,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws IdentityNotFoundException
     * @throws IdentityAlreadyErasedException
     * @throws IdentityNotPendingException
     * @throws InvalidConfirmationCodeException
     * @throws VerificationCodeNotFoundException
     * @throws VerificationCodeAttemptsExceededException
     * @throws IdentityAlreadyExistsException
     */
    public function __invoke(ConfirmIdentityEmail $command): void
    {
        $identity = $this->repository->load(IdentityId::fromString($command->id));
        $identity->activate($command->code, $this->verifier, $this->clock->now());

        $this->repository->save($identity);
    }
}
