<?php

declare(strict_types=1);

namespace Compliance\Erasure\Domain;

use Compliance\Erasure\Domain\Event\HoldLifted;
use Compliance\Erasure\Domain\Event\HoldPlaced;
use Compliance\Erasure\Domain\Event\SubjectErased;
use Compliance\Erasure\Domain\Event\SubjectErasureCancelled;
use Compliance\Erasure\Domain\Event\SubjectErasureRequested;
use Compliance\Erasure\Domain\Event\SubjectRegistered;
use Compliance\Erasure\Domain\Specification\ErasureRetentionExpiredSpecification;
use Compliance\Erasure\Domain\ValueObject\HoldReference;
use Compliance\Erasure\Domain\ValueObject\SubjectId;
use Compliance\Erasure\Domain\ValueObject\SubjectState;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootAttributeBehaviour;
use Patchlevel\EventSourcing\Aggregate\AggregateRootMetadataAware;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate('compliance.erasure.subject')]
final class Subject implements AggregateRoot, AggregateRootMetadataAware
{
    use AggregateRootAttributeBehaviour;

    #[Id]
    public private(set) SubjectId $id;
    public private(set) SubjectState $state;
    private \DateTimeImmutable $requestedAt;
    /** @var list<string> */
    private array $activeHolds = [];

    public static function place(SubjectId $id, HoldReference $reference, \DateTimeImmutable $placedAt): self
    {
        $self = new self();
        $self->recordThat(new SubjectRegistered(
            id: $id->toString(),
            registeredAt: $placedAt,
        ));
        $self->placeHold($reference, $placedAt);

        return $self;
    }

    public static function request(SubjectId $id, \DateTimeImmutable $requestedAt): self
    {
        $self = new self();
        $self->recordThat(new SubjectRegistered(
            id: $id->toString(),
            registeredAt: $requestedAt,
        ));
        $self->requestErasure($requestedAt);

        return $self;
    }

    public function placeHold(HoldReference $reference, \DateTimeImmutable $placedAt): void
    {
        if (\in_array($reference->toString(), $this->activeHolds, true)) {
            return;
        }

        $this->recordThat(new HoldPlaced(
            id: $this->id->toString(),
            reference: $reference->toString(),
            placedAt: $placedAt,
        ));
    }

    public function liftHold(HoldReference $reference, \DateTimeImmutable $liftedAt): void
    {
        if (!\in_array($reference->toString(), $this->activeHolds, true)) {
            return;
        }

        $this->recordThat(new HoldLifted(
            id: $this->id->toString(),
            reference: $reference->toString(),
            liftedAt: $liftedAt,
        ));
    }

    public function requestErasure(\DateTimeImmutable $requestedAt): void
    {
        if (SubjectState::RETAINED !== $this->state) {
            return;
        }

        $this->recordThat(new SubjectErasureRequested(
            id: $this->id->toString(),
            requestedAt: $requestedAt,
        ));
    }

    public function cancelErasure(\DateTimeImmutable $cancelledAt): void
    {
        if (SubjectState::ERASING !== $this->state) {
            return;
        }

        $this->recordThat(new SubjectErasureCancelled(
            id: $this->id->toString(),
            cancelledAt: $cancelledAt,
        ));
    }

    public function release(\DateTimeImmutable $now): void
    {
        if (SubjectState::ERASING !== $this->state) {
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
    private function applyPlaced(HoldPlaced $event): void
    {
        $this->activeHolds[] = $event->reference;
    }

    #[Apply]
    private function applyLifted(HoldLifted $event): void
    {
        $this->activeHolds = array_values(array_diff($this->activeHolds, [$event->reference]));
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
