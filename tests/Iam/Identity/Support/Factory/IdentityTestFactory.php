<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Support\Factory;

use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Reason;
use Shared\Tests\Support\Factory\AbstractAggregateTestFactory;
use Symfony\Component\Clock\Clock;
use Webmozart\Assert\Assert;

/**
 * @extends AbstractAggregateTestFactory<Identity>
 */
final class IdentityTestFactory extends AbstractAggregateTestFactory
{
    public function withId(string $id): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['id' => $id]));
    }

    public function withRegisteredAt(\DateTimeImmutable $registeredAt): self
    {
        return $this->withAttributes(array_merge($this->attributes, ['registeredAt' => $registeredAt]));
    }

    public function suspended(string $reason = 'Suspected fraudulent activity', ?\DateTimeImmutable $suspendedAt = null): self
    {
        $suspendedAt ??= Clock::get()->now();

        return $this->withModifier(static fn (Identity $identity) => $identity->suspend(Reason::fromString($reason), $suspendedAt));
    }

    public function reactivated(string $reason = 'Appeal upheld', ?\DateTimeImmutable $reactivatedAt = null): self
    {
        $reactivatedAt ??= Clock::get()->now();

        return $this->withModifier(static fn (Identity $identity) => $identity->reactivate(Reason::fromString($reason), $reactivatedAt));
    }

    public function erased(?\DateTimeImmutable $erasedAt = null): self
    {
        $erasedAt ??= Clock::get()->now();

        return $this->withModifier(static fn (Identity $identity) => $identity->erase($erasedAt));
    }

    protected function defaults(): array
    {
        return [
            'id' => IdentityId::generate()->toString(),
            'registeredAt' => self::faker()->dateTimeBetween('-1 year', '-1 day'),
        ];
    }

    protected function build(array $attributes): Identity
    {
        Assert::stringNotEmpty($id = $attributes['id']);
        Assert::isInstanceOf($registeredAt = $attributes['registeredAt'], \DateTimeInterface::class);

        return Identity::register(
            IdentityId::fromString($id),
            \DateTimeImmutable::createFromInterface($registeredAt),
        );
    }
}
