<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Command\ChangeEmail;

use Iam\Identity\Application\Command\ChangeEmail\Exception\IdentityEmailAlreadyInUseException;
use Iam\Identity\Application\IdentityUniqueKey;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityAlreadyExistsException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Exception\InvalidEmailChangeCodeException;
use Iam\Identity\Domain\Repository\IdentityRepositoryInterface;
use Iam\Identity\Domain\ValueObject\Email;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Shared\Domain\Exception\VerificationCodeAttemptsExceededException;
use Shared\Domain\Exception\VerificationCodeNotFoundException;
use Shared\Domain\Service\CodeChallengerInterface;

#[CommandHandler]
final readonly class ChangeEmailHandler
{
    public function __construct(
        private IdentityRepositoryInterface $repository,
        private UniquenessRegistryInterface $uniqueness,
        private CodeChallengerInterface $codeChallenger,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws IdentityNotFoundException
     * @throws IdentityAlreadyErasedException
     * @throws IdentityEmailAlreadyInUseException
     * @throws VerificationCodeNotFoundException
     * @throws VerificationCodeAttemptsExceededException
     * @throws InvalidEmailChangeCodeException
     * @throws IdentityAlreadyExistsException
     */
    public function __invoke(ChangeEmail $command): void
    {
        $newEmail = Email::fromString($command->newEmail);

        $this->uniqueness->release(UniqueKey::for(IdentityUniqueKey::EMAIL), $command->id);

        try {
            $this->uniqueness->claim(UniqueKey::for(IdentityUniqueKey::EMAIL), $newEmail->value, $command->id);
        } catch (UniquenessViolatedException $e) {
            throw IdentityEmailAlreadyInUseException::forEmail($newEmail->value, $e);
        }

        $identity = $this->repository->load(IdentityId::fromString($command->id));
        $identity->changeEmail($command->code, $this->codeChallenger, $newEmail, $this->clock->now());

        $this->repository->save($identity);
    }
}
