<?php

declare(strict_types=1);

namespace Iam\Identity\Domain;

use Iam\Identity\Domain\Event\IdentityRegistered;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate('iam.identity.identity')]
final class Identity implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    private IdentityId $id;
    private Login $login;

    public function id(): IdentityId
    {
        return $this->id;
    }

    public function login(): Login
    {
        return $this->login;
    }

    public static function register(IdentityId $id, Login $login, \DateTimeImmutable $registeredAt): self
    {
        $self = new self();
        $self->recordThat(new IdentityRegistered(
            id: $id->toString(),
            login: $login->toString(),
            registeredAt: $registeredAt->format('c'),
        ));

        return $self;
    }

    #[Apply]
    private function applyIdentityRegistered(IdentityRegistered $event): void
    {
        $this->id = IdentityId::fromString($event->id);
        $this->login = Login::fromString($event->login);
    }
}
