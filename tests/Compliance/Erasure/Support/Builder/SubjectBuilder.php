<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Support\Builder;

use Compliance\Erasure\Domain\Specification\ErasureRetentionExpiredSpecification;
use Compliance\Erasure\Domain\Subject;
use Compliance\Erasure\Domain\ValueObject\HoldReference;
use Compliance\Erasure\Domain\ValueObject\SubjectId;
use Ramsey\Uuid\Uuid;
use Support\Builder\AbstractAggregateBuilder;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: SubjectId,
 *     requestedAt: \DateTimeImmutable,
 *     cancelledAt: \DateTimeImmutable,
 *     reference: HoldReference,
 *     placedAt: \DateTimeImmutable,
 *     liftedAt: \DateTimeImmutable,
 *     releasedAt: \DateTimeImmutable,
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

    public function withRequestedAt(\DateTimeImmutable $requestedAt): self
    {
        return $this->withAttributes(requestedAt: $requestedAt);
    }

    public function heldBy(?HoldReference $reference = null, ?\DateTimeImmutable $placedAt = null): self
    {
        $builder = $this->withAttributes(...array_filter([
            'reference' => $reference,
            'placedAt' => $placedAt,
        ], static fn (mixed $value): bool => null !== $value));

        return $builder->withModifier(
            static fn (Subject $subject, self $builder) => $subject->placeHold($builder['reference'], $builder['placedAt']),
        );
    }

    public function liftedHold(?\DateTimeImmutable $liftedAt = null): self
    {
        $builder = null !== $liftedAt ? $this->withAttributes(liftedAt: $liftedAt) : $this;

        return $builder->withModifier(
            static fn (Subject $subject, self $builder) => $subject->liftHold($builder['reference'], $builder['liftedAt']),
        );
    }

    public function cancelled(?\DateTimeImmutable $cancelledAt = null): self
    {
        $builder = null !== $cancelledAt ? $this->withAttributes(cancelledAt: $cancelledAt) : $this;

        return $builder->withModifier(
            static fn (Subject $subject, self $builder) => $subject->cancelErasure($builder['cancelledAt']),
        );
    }

    public function released(?\DateTimeImmutable $releasedAt = null): self
    {
        $builder = null !== $releasedAt ? $this->withAttributes(releasedAt: $releasedAt) : $this;

        return $builder->withModifier(
            static fn (Subject $subject, self $builder) => $subject->release($builder['releasedAt']),
        );
    }

    protected static function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => static fn (): SubjectId => SubjectId::fromString(Uuid::uuid7()->toString()),
            'requestedAt' => static fn (): \DateTimeImmutable => $now,
            'cancelledAt' => static fn (): \DateTimeImmutable => $now->modify('+1 hour'),
            'reference' => static fn (): HoldReference => HoldReference::for('compliance.tests.source', Uuid::uuid7()->toString()),
            'placedAt' => static fn (): \DateTimeImmutable => $now,
            'liftedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 hour'),
            'releasedAt' => static fn (): \DateTimeImmutable => $now->modify(\sprintf('+%d days', ErasureRetentionExpiredSpecification::DAYS + 1)),
        ];
    }

    protected function build(): Subject
    {
        return Subject::request(
            id: $this['id'],
            requestedAt: $this['requestedAt'],
        );
    }
}
