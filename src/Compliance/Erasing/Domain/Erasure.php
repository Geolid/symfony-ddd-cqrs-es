<?php

declare(strict_types=1);

namespace Compliance\Erasing\Domain;

use Compliance\Erasing\Domain\Event\ErasureApproved;
use Compliance\Erasing\Domain\Event\ErasureCancelled;
use Compliance\Erasing\Domain\Event\ErasureRequested;
use Compliance\Erasing\Domain\Specification\ErasureRetentionExpiredSpecification;
use Compliance\Erasing\Domain\ValueObject\ErasureId;
use Compliance\Erasing\Domain\ValueObject\ErasureRequestState;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shared\Domain\Specification\CanTransitionToSpecification;

#[Aggregate('compliance.erasing.erasure')]
final class Erasure implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    /** @var array<string, list<ErasureRequestState>> */
    private const array STATE_TRANSITIONS = [
        ErasureRequestState::RETAINED->value => [ErasureRequestState::REQUESTED],
        ErasureRequestState::REQUESTED->value => [ErasureRequestState::RETAINED, ErasureRequestState::APPROVED],
        ErasureRequestState::APPROVED->value => [],
    ];

    #[Id]
    public private(set) ErasureId $id;
    public private(set) string $identityId;
    private ErasureRequestState $state;
    private \DateTimeImmutable $requestedAt;

    public static function request(ErasureId $id, string $identityId, \DateTimeImmutable $requestedAt): self
    {
        $self = new self();
        $self->recordThat(new ErasureRequested(
            id: $id->toString(),
            identityId: $identityId,
            requestedAt: $requestedAt,
        ));

        return $self;
    }

    public function reRequest(\DateTimeImmutable $requestedAt): void
    {
        if (!$this->canTransitionStateTo(ErasureRequestState::REQUESTED)) {
            return;
        }

        $this->recordThat(new ErasureRequested(
            id: $this->id->toString(),
            identityId: $this->identityId,
            requestedAt: $requestedAt,
        ));
    }

    public function cancel(\DateTimeImmutable $cancelledAt): void
    {
        if (!$this->canTransitionStateTo(ErasureRequestState::RETAINED)) {
            return;
        }

        $this->recordThat(new ErasureCancelled(
            id: $this->id->toString(),
            identityId: $this->identityId,
            cancelledAt: $cancelledAt,
        ));
    }

    public function approve(\DateTimeImmutable $now): void
    {
        if (!$this->canTransitionStateTo(ErasureRequestState::APPROVED)) {
            return;
        }

        if (!new ErasureRetentionExpiredSpecification($now)->isSatisfiedBy($this->requestedAt)) {
            return;
        }

        $this->recordThat(new ErasureApproved(
            id: $this->id->toString(),
            identityId: $this->identityId,
            approvedAt: $now,
        ));
    }

    private function canTransitionStateTo(ErasureRequestState $target): bool
    {
        return new CanTransitionToSpecification(self::STATE_TRANSITIONS, $target)->isSatisfiedBy($this->state);
    }

    #[Apply]
    private function applyRequested(ErasureRequested $event): void
    {
        $this->id = ErasureId::fromString($event->id);
        $this->identityId = $event->identityId;
        $this->state = ErasureRequestState::REQUESTED;
        $this->requestedAt = $event->requestedAt;
    }

    #[Apply]
    private function applyCancelled(ErasureCancelled $event): void
    {
        $this->state = ErasureRequestState::RETAINED;
    }

    #[Apply]
    private function applyApproved(ErasureApproved $event): void
    {
        $this->state = ErasureRequestState::APPROVED;
    }
}
