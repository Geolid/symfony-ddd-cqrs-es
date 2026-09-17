<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\ConfirmIdentityEmail;

use Iam\Identity\Application\Command\ConfirmIdentityEmail\Exception\InvalidConfirmationCodeException;
use Iam\Identity\Application\IdentityVerificationCodePurpose;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityAlreadyExistsException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Exception\IdentityNotPendingException;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\VerificationCode\Exception\VerificationCodeAttemptsExceededException;
use Shared\Application\VerificationCode\Exception\VerificationCodeNotFoundException;
use Shared\Application\VerificationCode\VerificationCode;

#[CommandHandler]
final readonly class ConfirmIdentityEmailHandler
{
    public function __construct(
        private IdentityRepositoryInterface $repository,
        private VerificationCode $verificationCode,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws VerificationCodeNotFoundException
     * @throws VerificationCodeAttemptsExceededException
     * @throws InvalidConfirmationCodeException
     * @throws IdentityNotFoundException
     * @throws IdentityAlreadyErasedException
     * @throws IdentityNotPendingException
     * @throws IdentityAlreadyExistsException
     */
    public function __invoke(ConfirmIdentityEmail $command): void
    {
        $now = $this->clock->now();

        if (!$this->verificationCode->verify(IdentityVerificationCodePurpose::EMAIL_CONFIRMATION, $command->id, $command->code, $now)) {
            throw InvalidConfirmationCodeException::forId($command->id);
        }

        $identity = $this->repository->load(IdentityId::fromString($command->id));
        $identity->activate($now);

        $this->repository->save($identity);
    }
}
