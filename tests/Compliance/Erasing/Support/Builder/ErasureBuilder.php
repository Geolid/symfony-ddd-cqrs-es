<?php

declare(strict_types=1);

namespace Compliance\Tests\Erasing\Support\Builder;

use Compliance\Erasing\Domain\Erasure;
use Compliance\Erasing\Domain\Specification\ErasureRetentionExpiredSpecification;
use Compliance\Erasing\Domain\ValueObject\ErasureId;
use Ramsey\Uuid\Uuid;
use Support\Builder\AbstractAggregateBuilder;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: ErasureId,
 *     identityId: string,
 *     requestedAt: \DateTimeImmutable,
 *     cancelledAt: \DateTimeImmutable,
 *     approvedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateBuilder<Erasure, Attributes>
 */
final class ErasureBuilder extends AbstractAggregateBuilder
{
    public function withId(string $id): self
    {
        return $this->withAttributes(id: ErasureId::fromString($id));
    }

    public function withIdentityId(string $identityId): self
    {
        return $this->withAttributes(identityId: $identityId);
    }

    public function withRequestedAt(\DateTimeImmutable $requestedAt): self
    {
        return $this->withAttributes(requestedAt: $requestedAt);
    }

    public function cancelled(?\DateTimeImmutable $cancelledAt = null): self
    {
        $builder = null !== $cancelledAt ? $this->withAttributes(cancelledAt: $cancelledAt) : $this;

        return $builder->withModifier(
            static fn (Erasure $erasure, self $builder) => $erasure->cancel($builder['cancelledAt']),
        );
    }

    public function approved(?\DateTimeImmutable $approvedAt = null): self
    {
        $builder = null !== $approvedAt ? $this->withAttributes(approvedAt: $approvedAt) : $this;

        return $builder->withModifier(
            static fn (Erasure $erasure, self $builder) => $erasure->approve($builder['approvedAt']),
        );
    }

    protected static function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => static fn (): ErasureId => ErasureId::fromString(Uuid::uuid7()->toString()),
            'identityId' => static fn (): string => Uuid::uuid7()->toString(),
            'requestedAt' => static fn (): \DateTimeImmutable => $now,
            'cancelledAt' => static fn (): \DateTimeImmutable => $now->modify('+1 hour'),
            'approvedAt' => static fn (): \DateTimeImmutable => $now->modify(\sprintf('+%d days', ErasureRetentionExpiredSpecification::DAYS + 1)),
        ];
    }

    protected function build(): Erasure
    {
        return Erasure::request(
            id: $this['id'],
            identityId: $this['identityId'],
            requestedAt: $this['requestedAt'],
        );
    }
}
