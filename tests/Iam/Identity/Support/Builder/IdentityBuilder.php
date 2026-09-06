<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Support\Builder;

use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Reason;
use Support\Builder\AbstractAggregateBuilder;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: IdentityId,
 *     registeredAt: \DateTimeImmutable,
 *     reason: Reason,
 *     suspendedAt: \DateTimeImmutable,
 *     reactivatedAt: \DateTimeImmutable,
 *     erasedAt: \DateTimeImmutable,
 * }
 *
 * @extends AbstractAggregateBuilder<Identity, Attributes>
 */
final class IdentityBuilder extends AbstractAggregateBuilder
{
    public function withId(string $id): self
    {
        return $this->withAttributes(id: IdentityId::fromString($id));
    }

    public function withRegisteredAt(\DateTimeImmutable $registeredAt): self
    {
        return $this->withAttributes(registeredAt: $registeredAt);
    }

    public function suspended(?string $reason = null, ?\DateTimeImmutable $suspendedAt = null): self
    {
        $builder = $this->withAttributes(...array_filter([
            'reason' => null !== $reason ? Reason::fromString($reason) : null,
            'suspendedAt' => $suspendedAt,
        ]));

        return $builder->withModifier(
            static fn (Identity $identity, self $builder) => $identity->suspend($builder['reason'], $builder['suspendedAt']),
        );
    }

    public function reactivated(?string $reason = null, ?\DateTimeImmutable $reactivatedAt = null): self
    {
        $builder = $this->withAttributes(...array_filter([
            'reason' => null !== $reason ? Reason::fromString($reason) : null,
            'reactivatedAt' => $reactivatedAt,
        ]));

        return $builder->withModifier(
            static fn (Identity $identity, self $builder) => $identity->reactivate($builder['reason'], $builder['reactivatedAt']),
        );
    }

    public function erased(?\DateTimeImmutable $erasedAt = null): self
    {
        $builder = null !== $erasedAt ? $this->withAttributes(erasedAt: $erasedAt) : $this;

        return $builder->withModifier(
            static fn (Identity $identity, self $builder) => $identity->erase($builder['erasedAt']),
        );
    }

    protected static function defaults(): array
    {
        $now = Clock::get()->now();

        return [
            'id' => IdentityId::generate(...),
            'registeredAt' => static fn (): \DateTimeImmutable => $now,
            'reason' => static fn (): Reason => Reason::fromString(SeededFaker::get()->sentence(4)),
            'suspendedAt' => static fn (): \DateTimeImmutable => $now->modify('+1 day'),
            'reactivatedAt' => static fn (): \DateTimeImmutable => $now->modify('+2 day'),
            'erasedAt' => static fn (): \DateTimeImmutable => $now->modify('+3 day'),
        ];
    }

    protected function build(): Identity
    {
        return Identity::register(
            id: $this['id'],
            registeredAt: $this['registeredAt'],
        );
    }
}
