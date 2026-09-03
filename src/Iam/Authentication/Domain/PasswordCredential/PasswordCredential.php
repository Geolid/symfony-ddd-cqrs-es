<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential;

use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialChanged;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialDefined;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialRehashed;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Login;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\Password;
use Iam\Authentication\Domain\PasswordCredential\ValueObject\PasswordCredentialId;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate('iam.authentication.password_credential')]
final class PasswordCredential implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    public private(set) PasswordCredentialId $id;
    private string $passwordHash;

    /**
     * @throws WeakPasswordException
     */
    public static function define(
        PasswordCredentialId $id,
        string $identityId,
        Login $login,
        #[\SensitiveParameter]
        Password $password,
        PasswordStrengthInterface $passwordStrength,
        PasswordHasherInterface $hasher,
        \DateTimeImmutable $definedAt,
    ): self {
        if (!$passwordStrength->isSufficient($password)) {
            throw WeakPasswordException::forIdentity($identityId);
        }

        $self = new self();
        $self->recordThat(new PasswordCredentialDefined(
            id: $id->toString(),
            identityId: $identityId,
            login: $login->value,
            passwordHash: $hasher->hash($password->value),
            definedAt: $definedAt,
        ));

        return $self;
    }

    /**
     * @throws WeakPasswordException
     * @throws SamePasswordException
     */
    public function change(#[\SensitiveParameter] Password $password, PasswordStrengthInterface $passwordStrength, PasswordHasherInterface $hasher, \DateTimeImmutable $changedAt): void
    {
        if (!$passwordStrength->isSufficient($password)) {
            throw WeakPasswordException::forPasswordCredential($this->id);
        }

        if ($hasher->verify($this->passwordHash, $password->value)) {
            throw SamePasswordException::forId($this->id);
        }

        $this->recordThat(new PasswordCredentialChanged(
            id: $this->id->toString(),
            passwordHash: $hasher->hash($password->value),
            changedAt: $changedAt,
        ));
    }

    // Raw string, not Password: re-validating an already-accepted secret could fail if invariants tightened since.
    public function rehash(#[\SensitiveParameter] string $plainPassword, PasswordHasherInterface $hasher, \DateTimeImmutable $rehashedAt): void
    {
        $this->recordThat(new PasswordCredentialRehashed(
            id: $this->id->toString(),
            passwordHash: $hasher->hash($plainPassword),
            rehashedAt: $rehashedAt,
        ));
    }

    #[Apply]
    private function applyDefined(PasswordCredentialDefined $event): void
    {
        $this->id = PasswordCredentialId::fromString($event->id);
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
}
