<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Support\Factory;

use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Reason;
use Support\Factory\AbstractAggregateTestFactory;
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
 * @extends AbstractAggregateTestFactory<Identity, Attributes>
 */
final class IdentityTestFactory extends AbstractAggregateTestFactory
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
        $factory = $this->withAttributes(...array_filter([
            'reason' => null !== $reason ? Reason::fromString($reason) : null,
            'suspendedAt' => $suspendedAt,
        ]));

        return $factory->withModifier(
            static fn (Identity $identity, self $factory) => $identity->suspend($factory['reason'], $factory['suspendedAt']),
        );
    }

    public function reactivated(?string $reason = null, ?\DateTimeImmutable $reactivatedAt = null): self
    {
        $factory = $this->withAttributes(...array_filter([
            'reason' => null !== $reason ? Reason::fromString($reason) : null,
            'reactivatedAt' => $reactivatedAt,
        ]));

        return $factory->withModifier(
            static fn (Identity $identity, self $factory) => $identity->reactivate($factory['reason'], $factory['reactivatedAt']),
        );
    }

    public function erased(?\DateTimeImmutable $erasedAt = null): self
    {
        $factory = null !== $erasedAt ? $this->withAttributes(erasedAt: $erasedAt) : $this;

        return $factory->withModifier(
            static fn (Identity $identity, self $factory) => $identity->erase($factory['erasedAt']),
        );
    }

    protected static function defaults(): array
    {
        return [
            'id' => IdentityId::generate(...),
            'registeredAt' => static fn (): \DateTimeImmutable => Clock::get()->now(),
            'reason' => static fn (): Reason => Reason::fromString(SeededFaker::get()->sentence(4)),
            'suspendedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+1 day'),
            'reactivatedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+2 day'),
            'erasedAt' => static fn (): \DateTimeImmutable => Clock::get()->now()->modify('+3 day'),
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
