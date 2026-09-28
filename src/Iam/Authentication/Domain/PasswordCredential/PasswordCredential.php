<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential;

use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialChanged;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialDefined;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialRehashed;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialReset;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialResetRequested;
use Iam\Authentication\Domain\PasswordCredential\Exception\InvalidPasswordResetCodeException;
use Iam\Authentication\Domain\PasswordCredential\Exception\PasswordResetRequestedTooRecentlyException;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Specification\PasswordStrengthSpecificationInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialVerificationCodePurpose;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shared\Domain\Exception\VerificationCodeAttemptsExceededException;
use Shared\Domain\Exception\VerificationCodeNotFoundException;
use Shared\Domain\Service\CodeChallengerInterface;
use Shared\Domain\Service\CooldownCalculator;
use Shared\Domain\Specification\CooldownElapsedSpecification;
use Shared\Domain\ValueObject\VerificationCodeKey;

#[Aggregate('iam.authentication.password_credential')]
final class PasswordCredential implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    public private(set) PasswordCredentialId $id;
    private string $identityId;
    private string $passwordHash;
    private ?\DateTimeImmutable $resetRequestedAt = null;

    /**
     * @throws WeakPasswordException
     */
    public static function define(
        PasswordCredentialId $id,
        string $identityId,
        #[\SensitiveParameter]
        Password $password,
        PasswordStrengthSpecificationInterface $passwordStrengthSpecification,
        PasswordHasherInterface $hasher,
        \DateTimeImmutable $definedAt,
    ): self {
        if (!$passwordStrengthSpecification->isSatisfiedBy($password)) {
            throw WeakPasswordException::forIdentity($identityId);
        }

        $self = new self();
        $self->recordThat(new PasswordCredentialDefined(
            id: $id,
            identityId: $identityId,
            passwordHash: $hasher->hash($password->value),
            definedAt: $definedAt,
        ));

        return $self;
    }

    /**
     * @throws WeakPasswordException
     * @throws SamePasswordException
     */
    public function change(#[\SensitiveParameter] Password $password, PasswordStrengthSpecificationInterface $passwordStrengthSpecification, PasswordHasherInterface $hasher, \DateTimeImmutable $changedAt): void
    {
        if (!$passwordStrengthSpecification->isSatisfiedBy($password)) {
            throw WeakPasswordException::forPasswordCredential($this->id);
        }

        if ($hasher->verify($this->passwordHash, $password->value)) {
            throw SamePasswordException::forId($this->id);
        }

        $this->recordThat(new PasswordCredentialChanged(
            id: $this->id,
            passwordHash: $hasher->hash($password->value),
            changedAt: $changedAt,
        ));
    }

    /**
     * @throws PasswordResetRequestedTooRecentlyException
     */
    public function requestReset(\DateTimeImmutable $requestedAt): void
    {
        $cooldownCalculator = new CooldownCalculator();
        if (!new CooldownElapsedSpecification($cooldownCalculator, $requestedAt)->isSatisfiedBy($this->resetRequestedAt)) {
            \assert(null !== $this->resetRequestedAt);

            throw PasswordResetRequestedTooRecentlyException::forId($this->id, $cooldownCalculator->retryAt($this->resetRequestedAt));
        }

        $this->recordThat(new PasswordCredentialResetRequested(
            id: $this->id,
            identityId: $this->identityId,
            requestedAt: $requestedAt,
        ));
    }

    /**
     * @throws InvalidPasswordResetCodeException
     * @throws VerificationCodeNotFoundException
     * @throws VerificationCodeAttemptsExceededException
     * @throws WeakPasswordException
     * @throws SamePasswordException
     */
    public function resetPassword(#[\SensitiveParameter] string $code, CodeChallengerInterface $codeChallenger, #[\SensitiveParameter] Password $newPassword, PasswordStrengthSpecificationInterface $passwordStrengthSpecification, PasswordHasherInterface $hasher, \DateTimeImmutable $resetAt): void
    {
        if (!$codeChallenger->verify(VerificationCodeKey::for(PasswordCredentialVerificationCodePurpose::PASSWORD_RESET, $this->identityId), $code, $resetAt)) {
            throw InvalidPasswordResetCodeException::forId($this->id);
        }

        if (!$passwordStrengthSpecification->isSatisfiedBy($newPassword)) {
            throw WeakPasswordException::forPasswordCredential($this->id);
        }

        if ($hasher->verify($this->passwordHash, $newPassword->value)) {
            throw SamePasswordException::forId($this->id);
        }

        $this->recordThat(new PasswordCredentialReset(
            id: $this->id,
            passwordHash: $hasher->hash($newPassword->value),
            resetAt: $resetAt,
        ));
    }

    // Raw string, not Password: re-validating an already-accepted password could fail if invariants tightened since.
    public function rehash(#[\SensitiveParameter] string $plainPassword, PasswordHasherInterface $hasher, \DateTimeImmutable $rehashedAt): void
    {
        $this->recordThat(new PasswordCredentialRehashed(
            id: $this->id,
            passwordHash: $hasher->hash($plainPassword),
            rehashedAt: $rehashedAt,
        ));
    }

    #[Apply]
    private function applyDefined(PasswordCredentialDefined $event): void
    {
        $this->id = $event->id;
        $this->identityId = $event->identityId;
        $this->passwordHash = $event->passwordHash;
    }

    #[Apply]
    private function applyChanged(PasswordCredentialChanged $event): void
    {
        $this->passwordHash = $event->passwordHash;
    }

    #[Apply]
    private function applyRehashed(PasswordCredentialRehashed $event): void
    {
        $this->passwordHash = $event->passwordHash;
    }

    #[Apply]
    private function applyResetRequested(PasswordCredentialResetRequested $event): void
    {
        $this->resetRequestedAt = $event->requestedAt;
    }

    #[Apply]
    private function applyReset(PasswordCredentialReset $event): void
    {
        $this->passwordHash = $event->passwordHash;
    }
}
