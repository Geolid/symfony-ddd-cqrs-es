<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasure\Support\Builder;

use Compliance\Erasure\Domain\ErasureHold;
use Compliance\Erasure\Domain\Specification\ErasureRetentionExpiredSpecification;
use Compliance\Erasure\Domain\Subject;
use Compliance\Erasure\Domain\ValueObject\ErasureHoldReference;
use Compliance\Erasure\Domain\ValueObject\SubjectId;
use Ramsey\Uuid\Uuid;
use Support\Builder\AbstractAggregateBuilder;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @phpstan-type Attributes = array{
 *     id: SubjectId,
 *     identityId: string,
 *     registeredAt: \DateTimeImmutable,
 *     requestedAt: \DateTimeImmutable,
 *     cancelledAt: \DateTimeImmutable,
 *     activeHolds: array<string, ErasureHold>,
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

    public function withIdentityId(string $identityId): self
    {
        return $this->withAttributes(identityId: $identityId);
    }

    public function withActiveHolds(ErasureHold ...$activeHolds): self
    {
        $keyed = [];
        foreach ($activeHolds as $hold) {
            $keyed[$hold->reference->toString()] = $hold;
        }

        return $this->withAttributes(activeHolds: $keyed);
    }

    public function erasureHoldPlaced(?ErasureHoldReference $reference = null, ?\DateTimeImmutable $placedAt = null): self
    {
        $hold = new ErasureHold(
            $reference ?? ErasureHoldReference::for('compliance.tests.source', Uuid::uuid7()->toString()),
            $placedAt ?? Clock::get()->now()->modify('+1 day'),
        );

        $builder = $this->withAttributes(activeHolds: [...$this['activeHolds'], $hold->reference->toString() => $hold]);

        return $builder->withModifier(
            static function (Subject $subject, self $builder): void {
                foreach ($builder['activeHolds'] as $hold) {
                    $subject->placeErasureHold($hold->reference, $hold->placedAt);
                }
            },
        );
    }

    public function erasureHoldLifted(?ErasureHoldReference $reference = null, ?\DateTimeImmutable $liftedAt = null): self
    {
        $activeHolds = $this['activeHolds'];
        Assert::notEmpty($activeHolds, 'erasureHoldLifted() needs an active hold to lift — place one first, or pass an explicit reference.');
        $hold = null !== $reference ? $activeHolds[$reference->toString()] : array_last($activeHolds);
        $reference = $hold->reference;
        $liftedAt ??= $hold->placedAt->modify('+2 days');

        unset($activeHolds[$reference->toString()]);
        $builder = $this->withAttributes(activeHolds: $activeHolds);

        return $builder->withModifier(
            static fn (Subject $subject, self $builder) => $subject->liftErasureHold($reference, $liftedAt),
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
            'id' => static fn (?self $builder): SubjectId => SubjectId::forIdentity(
                null !== $builder ? $builder['identityId'] : self::sample('identityId'),
            ),
            'identityId' => static fn (): string => Uuid::uuid7()->toString(),
            'registeredAt' => static fn (): \DateTimeImmutable => $now,
            'requestedAt' => static fn (): \DateTimeImmutable => $now,
            'cancelledAt' => static fn (): \DateTimeImmutable => $now->modify('+1 hour'),
            'activeHolds' => static fn (): array => [],
            'erasedAt' => static fn (): \DateTimeImmutable => $now->modify(\sprintf('+%d days', ErasureRetentionExpiredSpecification::DAYS + 1)),
        ];
    }

    protected function build(): Subject
    {
        return Subject::register(
            id: $this['id'],
            identityId: $this['identityId'],
            registeredAt: $this['registeredAt'],
        );
    }
}
