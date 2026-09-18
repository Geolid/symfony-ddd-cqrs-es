<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\ResetPassword;

use Iam\Authentication\Application\CredentialVerification\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Authentication\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Exception\InvalidPasswordResetCodeException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialAlreadyExistsException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialNotFoundException;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\Repository\PasswordCredentialRepositoryInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Domain\Exception\VerificationCodeAttemptsExceededException;
use Shared\Domain\Exception\VerificationCodeNotFoundException;
use Shared\Domain\Service\VerificationCodeInterface;

#[CommandHandler]
final readonly class ResetPasswordHandler
{
    public function __construct(
        private PasswordCredentialRepositoryInterface $repository,
        private IdentityFinderInterface $identityFinder,
        private VerificationCodeInterface $verificationCode,
        private PasswordStrengthSpecificationInterface $passwordStrengthSpecification,
        private PasswordHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws IdentityResultNotFoundException
     * @throws IdentityNotAuthenticatableException
     * @throws PasswordCredentialNotFoundException
     * @throws InvalidPasswordResetCodeException
     * @throws VerificationCodeNotFoundException
     * @throws VerificationCodeAttemptsExceededException
     * @throws WeakPasswordException
     * @throws SamePasswordException
     * @throws PasswordCredentialAlreadyExistsException
     */
    public function __invoke(ResetPassword $command): void
    {
        $identity = $this->identityFinder->ofId($command->identityId);

        if (!$identity->status->isActive()) {
            throw IdentityNotAuthenticatableException::forIdentity($command->identityId);
        }

        $credential = $this->repository->load(PasswordCredentialId::forIdentity($command->identityId));
        $credential->resetPassword(
            $command->code,
            $this->verificationCode,
            Password::fromString($command->newPassword),
            $this->passwordStrengthSpecification,
            $this->hasher,
            $this->clock->now(),
        );

        $this->repository->save($credential);
    }
}
