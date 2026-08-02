<?php

declare(strict_types=1);

namespace Iam\Identity\Domain;

use Iam\Identity\Domain\Event\PasswordChanged;
use Iam\Identity\Domain\Event\PasswordSet;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate('iam.identity.password')]
final class Password implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    private PasswordId $id;
    private string $hash;

    public function id(): PasswordId
    {
        return $this->id;
    }

    public function hash(): string
    {
        return $this->hash;
    }

    public static function set(PasswordId $id, IdentityId $identityId, string $hash, \DateTimeImmutable $setAt): self
    {
        $self = new self();
        $self->recordThat(new PasswordSet(
            id: $id->toString(),
            identityId: $identityId->toString(),
            hash: $hash,
            setAt: $setAt->format('c'),
        ));

        return $self;
    }

    public function change(string $hash, \DateTimeImmutable $changedAt): void
    {
        $this->recordThat(new PasswordChanged(
            id: $this->id->toString(),
            hash: $hash,
            changedAt: $changedAt->format('c'),
        ));
    }

    #[Apply]
    private function applyPasswordSet(PasswordSet $event): void
    {
        $this->id = PasswordId::fromString($event->id);
        $this->hash = $event->hash;
    }

    #[Apply]
    private function applyPasswordChanged(PasswordChanged $event): void
    {
        $this->hash = $event->hash;
    }
}
