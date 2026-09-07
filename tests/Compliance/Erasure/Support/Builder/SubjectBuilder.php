<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Support\Builder;

use Compliance\Erasure\Domain\Specification\ErasureRetentionExpiredSpecification;
use Compliance\Erasure\Domain\Subject;
use Compliance\Erasure\Domain\ValueObject\ErasureHoldReference;
use Compliance\Erasure\Domain\ValueObject\SubjectId;
use Ramsey\Uuid\Uuid;
use Support\Builder\AbstractAggregateBuilder;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: SubjectId,
 *     registeredAt: \DateTimeImmutable,
 *     requestedAt: \DateTimeImmutable,
 *     cancelledAt: \DateTimeImmutable,
 *     reference: ErasureHoldReference,
 *     erasureHoldPlacedAt: \DateTimeImmutable,
 *     erasureHoldLiftedAt: \DateTimeImmutable,
 *     erasedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateBuilder<Subject, Attributes>
 */
final class SubjectBuilder extends AbstractAggregateBuilder
{
    public function withId(string $id): self
    {
        return $this->withAttributes(id: SubjectId::fromString($id));
    }

    public function erasureHoldPlaced(?ErasureHoldReference $reference = null, ?\DateTimeImmutable $erasureHoldPlacedAt = null): self
    {
        $builder = $this->withAttributes(...array_filter([
            'reference' => $reference,
            'erasureHoldPlacedAt' => $erasureHoldPlacedAt,
        ], static fn (mixed $value): bool => null !== $value));

        return $builder->withModifier(
            static fn (Subject $subject, self $builder) => $subject->placeErasureHold($builder['reference'], $builder['erasureHoldPlacedAt']),
        );
    }

    public function erasureHoldLifted(?\DateTimeImmutable $erasureHoldLiftedAt = null): self
    {
        $builder = null !== $erasureHoldLiftedAt ? $this->withAttributes(erasureHoldLiftedAt: $erasureHoldLiftedAt) : $this;

        return $builder->withModifier(
            static fn (Subject $subject, self $builder) => $subject->liftErasureHold($builder['reference'], $builder['erasureHoldLiftedAt']),
        );
    }

    public function erasureRequested(?\DateTimeImmutable $requestedAt = null): self
    {
        $builder = null !== $requestedAt ? $this->withAttributes(requestedAt: $requestedAt) : $this;

        return $builder->withModifier(
            static fn (Subject $subject, self $builder) => $subject->requestErasure($builder['requestedAt']),
        );
    }

    public function erasureCancelled(?\DateTimeImmutable $cancelledAt = null): self
    {
        $builder = null !== $cancelledAt ? $this->withAttributes(cancelledAt: $cancelledAt) : $this;

        return $builder->withModifier(
            static fn (Subject $subject, self $builder) => $subject->cancelErasure($builder['cancelledAt']),
        );
    }

    public function erased(?\DateTimeImmutable $erasedAt = null): self
    {
        $builder = null !== $erasedAt ? $this->withAttributes(erasedAt: $erasedAt) : $this;

        return $builder->withModifier(
            static fn (Subject $subject, self $builder) => $subject->erase($builder['erasedAt']),
        );
    }

    protected static function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => static fn (): SubjectId => SubjectId::fromString(Uuid::uuid7()->toString()),
            'registeredAt' => static fn (): \DateTimeImmutable => $now,
            'requestedAt' => static fn (): \DateTimeImmutable => $now,
            'cancelledAt' => static fn (): \DateTimeImmutable => $now->modify('+1 hour'),
            'reference' => static fn (): ErasureHoldReference => ErasureHoldReference::for('compliance.tests.source', Uuid::uuid7()->toString()),
            'erasureHoldPlacedAt' => static fn (): \DateTimeImmutable => $now,
            'erasureHoldLiftedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 hour'),
            'erasedAt' => static fn (): \DateTimeImmutable => $now->modify(\sprintf('+%d days', ErasureRetentionExpiredSpecification::DAYS + 1)),
        ];
    }

    protected function build(): Subject
    {
        return Subject::register(
            id: $this['id'],
            registeredAt: $this['registeredAt'],
        );
    }
}
