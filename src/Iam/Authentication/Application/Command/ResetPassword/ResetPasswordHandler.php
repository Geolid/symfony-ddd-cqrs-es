<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Command\ResetPassword;

use Iam\Authentication\Application\AuthenticationVerificationCodePurpose;
use Iam\Authentication\Application\Command\ResetPassword\Exception\InvalidPasswordResetCodeException;
use Iam\Authentication\Application\CredentialVerification\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Finder\Identity\Exception\IdentityResultNotFoundException;
use Iam\Authentication\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialAlreadyExistsException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordCredentialNotFoundException;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\PasswordCredential;
use Iam\Authentication\Domain\PasswordCredential\Repository\PasswordCredentialRepositoryInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandHandler;
use Shared\Application\VerificationCode\Exception\VerificationCodeAttemptsExceededException;
use Shared\Application\VerificationCode\Exception\VerificationCodeNotFoundException;
use Shared\Application\VerificationCode\VerificationCode;

#[CommandHandler]
final readonly class ResetPasswordHandler
{
    public function __construct(
        private PasswordCredentialRepositoryInterface $repository,
        private IdentityFinderInterface $identityFinder,
        private VerificationCode $verificationCode,
        private PasswordStrengthSpecificationInterface $passwordStrengthSpecification,
        private PasswordHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws VerificationCodeNotFoundException
     * @throws VerificationCodeAttemptsExceededException
     * @throws InvalidPasswordResetCodeException
     * @throws IdentityResultNotFoundException
     * @throws IdentityNotAuthenticatableException
     * @throws WeakPasswordException
     * @throws SamePasswordException
     * @throws PasswordCredentialAlreadyExistsException
     */
    public function __invoke(ResetPassword $command): void
    {
        $now = $this->clock->now();

        if (!$this->verificationCode->verify(AuthenticationVerificationCodePurpose::PASSWORD_RESET, $command->identityId, $command->code, $now)) {
            throw InvalidPasswordResetCodeException::forIdentity($command->identityId);
        }

        $identity = $this->identityFinder->ofId($command->identityId);

        if (!$identity->status->isActive()) {
            throw IdentityNotAuthenticatableException::forIdentity($command->identityId);
        }

        $password = Password::fromString($command->newPassword);

        try {
            $credential = $this->repository->load(PasswordCredentialId::forIdentity($command->identityId));
            $credential->change($password, $this->passwordStrengthSpecification, $this->hasher, $now);
        } catch (PasswordCredentialNotFoundException) {
            $credential = PasswordCredential::define(
                id: PasswordCredentialId::forIdentity($command->identityId),
                identityId: $command->identityId,
                password: $password,
                passwordStrengthSpecification: $this->passwordStrengthSpecification,
                hasher: $this->hasher,
                definedAt: $now,
            );
        }

        $this->repository->save($credential);
    }
}
