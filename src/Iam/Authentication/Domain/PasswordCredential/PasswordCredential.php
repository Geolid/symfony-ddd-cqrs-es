<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\PasswordCredential;

use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialChanged;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialDefined;
use Iam\Authentication\Domain\PasswordCredential\Event\PasswordCredentialRehashed;
use Iam\Authentication\Domain\PasswordCredential\Exception\CompromisedPasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordPolicyInterface;
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
    public private(set) Login $login;
    private string $passwordHash;

    /**
     * @throws WeakPasswordException
     * @throws CompromisedPasswordException
     */
    public static function define(
        PasswordCredentialId $id,
        string $identityId,
        Login $login,
        #[\SensitiveParameter]
        Password $password,
        PasswordPolicyInterface $policy,
        PasswordHasherInterface $hasher,
        \DateTimeImmutable $definedAt,
    ): self {
        if (!$policy->isStrongEnough($password)) {
            throw WeakPasswordException::forIdentity($identityId);
        }

        if ($policy->isCompromised($password)) {
            throw CompromisedPasswordException::forIdentity($identityId);
        }

        $self = new self();
        $self->recordThat(new PasswordCredentialDefined(
            id: $id->toString(),
            identityId: $identityId,
            login: $login->value,
            passwordHash: $hasher->hash($password->value),
            definedAt: $definedAt->format(\DateTimeInterface::ATOM),
        ));

        return $self;
    }

    /**
     * @throws WeakPasswordException
     * @throws CompromisedPasswordException
     * @throws SamePasswordException
     */
    public function change(#[\SensitiveParameter] Password $password, PasswordPolicyInterface $policy, PasswordHasherInterface $hasher, \DateTimeImmutable $changedAt): void
    {
        if (!$policy->isStrongEnough($password)) {
            throw WeakPasswordException::forPasswordCredential($this->id);
        }

        if ($policy->isCompromised($password)) {
            throw CompromisedPasswordException::forPasswordCredential($this->id);
        }

        if ($hasher->verify($this->passwordHash, $password->value)) {
            throw SamePasswordException::forId($this->id);
        }

        $this->recordThat(new PasswordCredentialChanged(
            id: $this->id->toString(),
            passwordHash: $hasher->hash($password->value),
            changedAt: $changedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    public function rehash(#[\SensitiveParameter] string $plainPassword, PasswordHasherInterface $hasher, \DateTimeImmutable $rehashedAt): void
    {
        $this->recordThat(new PasswordCredentialRehashed(
            id: $this->id->toString(),
            passwordHash: $hasher->hash($plainPassword),
            rehashedAt: $rehashedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    #[Apply]
    private function applyDefined(PasswordCredentialDefined $event): void
    {
        $this->id = PasswordCredentialId::fromString($event->id);
        $this->login = Login::fromString($event->login);
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
