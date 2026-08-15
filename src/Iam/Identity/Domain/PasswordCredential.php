<?php

declare(strict_types=1);

namespace Iam\Identity\Domain;

use Iam\Identity\Domain\Event\PasswordCredentialChanged;
use Iam\Identity\Domain\Event\PasswordCredentialDefined;
use Iam\Identity\Domain\Event\PasswordCredentialRehashed;
use Iam\Identity\Domain\Service\SecretHasherInterface;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Login;
use Iam\Identity\Domain\ValueObject\PasswordCredentialId;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate('iam.identity.password_credential')]
final class PasswordCredential implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    private PasswordCredentialId $id;
    private Login $login;

    public function id(): PasswordCredentialId
    {
        return $this->id;
    }

    public function login(): Login
    {
        return $this->login;
    }

    public static function define(
        PasswordCredentialId $id,
        IdentityId $identityId,
        Login $login,
        string $plainPassword,
        SecretHasherInterface $hasher,
        \DateTimeImmutable $definedAt,
    ): self {
        $self = new self();
        $self->recordThat(new PasswordCredentialDefined(
            id: $id->toString(),
            identityId: $identityId->toString(),
            login: $login->toString(),
            hash: $hasher->hash($plainPassword),
            setAt: $definedAt->format(\DateTimeInterface::ATOM),
        ));

        return $self;
    }

    public function change(string $plainPassword, SecretHasherInterface $hasher, \DateTimeImmutable $changedAt): void
    {
        $this->recordThat(new PasswordCredentialChanged(
            id: $this->id->toString(),
            hash: $hasher->hash($plainPassword),
            changedAt: $changedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    public function rehash(string $plainPassword, SecretHasherInterface $hasher, \DateTimeImmutable $rehashedAt): void
    {
        $this->recordThat(new PasswordCredentialRehashed(
            id: $this->id->toString(),
            hash: $hasher->hash($plainPassword),
            rehashedAt: $rehashedAt->format(\DateTimeInterface::ATOM),
        ));
    }

    #[Apply]
    private function applyPasswordCredentialDefined(PasswordCredentialDefined $event): void
    {
        $this->id = PasswordCredentialId::fromString($event->id);
        $this->login = Login::fromString($event->login);
    }

    #[Apply]
    private function applyPasswordCredentialChanged(PasswordCredentialChanged $event): void
    {
    }

    #[Apply]
    private function applyPasswordCredentialRehashed(PasswordCredentialRehashed $event): void
    {
    }
}
