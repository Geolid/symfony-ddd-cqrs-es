<?php

declare(strict_types=1);

namespace Compliance\Erasure\Domain;

use Compliance\Erasure\Domain\Event\SubjectErased;
use Compliance\Erasure\Domain\Event\SubjectErasureCancelled;
use Compliance\Erasure\Domain\Event\SubjectErasureHoldLifted;
use Compliance\Erasure\Domain\Event\SubjectErasureHoldPlaced;
use Compliance\Erasure\Domain\Event\SubjectErasureRequested;
use Compliance\Erasure\Domain\Event\SubjectRegistered;
use Compliance\Erasure\Domain\Specification\ErasureRetentionExpiredSpecification;
use Compliance\Erasure\Domain\ValueObject\ErasureHoldReference;
use Compliance\Erasure\Domain\ValueObject\SubjectId;
use Compliance\Erasure\Domain\ValueObject\SubjectState;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;
use Shared\Domain\Specification\CanTransitionToSpecification;

#[Aggregate('compliance.erasure.subject')]
final class Subject implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    /** @var array<string, list<SubjectState>> */
    private const array TRANSITIONS = [
        SubjectState::RETAINED->value => [SubjectState::ERASING],
        SubjectState::ERASING->value => [SubjectState::RETAINED, SubjectState::ERASED],
        SubjectState::ERASED->value => [],
    ];

    #[Id]
    public private(set) SubjectId $id;
    public private(set) SubjectState $state;
    private \DateTimeImmutable $requestedAt;
    /** @var array<string, ErasureHold> */
    private array $activeHolds = [];

    public static function register(SubjectId $id, \DateTimeImmutable $registeredAt): self
    {
        $self = new self();
        $self->recordThat(new SubjectRegistered(
            id: $id->toString(),
            registeredAt: $registeredAt,
        ));

        return $self;
    }

    public function placeErasureHold(ErasureHoldReference $reference, \DateTimeImmutable $placedAt): void
    {
        if (isset($this->activeHolds[$reference->toString()])) {
            return;
        }

        $this->recordThat(new SubjectErasureHoldPlaced(
            id: $this->id->toString(),
            reference: $reference,
            placedAt: $placedAt,
        ));
    }

    public function liftErasureHold(ErasureHoldReference $reference, \DateTimeImmutable $liftedAt): void
    {
        if (!isset($this->activeHolds[$reference->toString()])) {
            return;
        }

        $this->recordThat(new SubjectErasureHoldLifted(
            id: $this->id->toString(),
            reference: $reference,
            liftedAt: $liftedAt,
        ));
    }

    public function requestErasure(\DateTimeImmutable $requestedAt): void
    {
        if (!new CanTransitionToSpecification(self::TRANSITIONS, SubjectState::ERASING)->isSatisfiedBy($this->state)) {
            return;
        }

        $this->recordThat(new SubjectErasureRequested(
            id: $this->id->toString(),
            requestedAt: $requestedAt,
        ));
    }

    public function cancelErasure(\DateTimeImmutable $cancelledAt): void
    {
        if (!new CanTransitionToSpecification(self::TRANSITIONS, SubjectState::RETAINED)->isSatisfiedBy($this->state)) {
            return;
        }

        $this->recordThat(new SubjectErasureCancelled(
            id: $this->id->toString(),
            cancelledAt: $cancelledAt,
        ));
    }

    public function erase(\DateTimeImmutable $now): void
    {
        if (!new CanTransitionToSpecification(self::TRANSITIONS, SubjectState::ERASED)->isSatisfiedBy($this->state)) {
            return;
        }

        if (!new ErasureRetentionExpiredSpecification($now)->isSatisfiedBy($this->requestedAt)) {
            return;
        }

        if (\count($this->activeHolds) > 0) {
            return;
        }

        $this->recordThat(new SubjectErased(
            id: $this->id->toString(),
            erasedAt: $now,
        ));
    }

    #[Apply]
    private function applyRegistered(SubjectRegistered $event): void
    {
        $this->id = SubjectId::fromString($event->id);
        $this->state = SubjectState::RETAINED;
    }

    #[Apply]
    private function applyErasureHoldPlaced(SubjectErasureHoldPlaced $event): void
    {
        $this->activeHolds[$event->reference->toString()] = new ErasureHold($event->reference, $event->placedAt);
    }

    #[Apply]
    private function applyErasureHoldLifted(SubjectErasureHoldLifted $event): void
    {
        unset($this->activeHolds[$event->reference->toString()]);
    }

    #[Apply]
    private function applyErasureRequested(SubjectErasureRequested $event): void
    {
        $this->state = SubjectState::ERASING;
        $this->requestedAt = $event->requestedAt;
    }

    #[Apply]
    private function applyErasureCancelled(SubjectErasureCancelled $event): void
    {
        $this->state = SubjectState::RETAINED;
    }

    #[Apply]
    private function applyErased(SubjectErased $event): void
    {
        $this->state = SubjectState::ERASED;
    }
}
