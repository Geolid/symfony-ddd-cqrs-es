<?php

declare(strict_types=1);

namespace Iam\Tests\Identity\Support\Factory;

use Iam\Identity\Domain\Identity;
use Iam\Identity\Domain\ValueObject\IdentityId;
use Iam\Identity\Domain\ValueObject\Reason;
use Support\ClockSequence;
use Support\Factory\AbstractAggregateTestFactory;
use Support\SeededFaker;
use Symfony\Component\Clock\Clock;

/**
 * @phpstan-type Attributes = array{
 *     id: IdentityId,
 *     registeredAt: \DateTimeImmutable,
 *     reason?: string,
 * }
 *
 * @extends AbstractAggregateTestFactory<Identity, Attributes>
 */
final class IdentityTestFactory extends AbstractAggregateTestFactory
{
    public function withId(string $id): self
    {
        return $this->withAttributes(['id' => IdentityId::fromString($id)]);
    }

    public function withRegisteredAt(\DateTimeImmutable $registeredAt): self
    {
        return $this->withAttributes(['registeredAt' => $registeredAt]);
    }

    public function suspended(?string $reason = null, ?\DateTimeImmutable $suspendedAt = null): self
    {
        $reason ??= SeededFaker::get()->sentence(4);
        $suspendedAt ??= Clock::get()->now();

        return $this->withAttributes(['reason' => $reason])
            ->withModifier(static fn (Identity $identity) => $identity->suspend(Reason::fromString($reason), $suspendedAt));
    }

    public function reactivated(?string $reason = null, ?\DateTimeImmutable $reactivatedAt = null): self
    {
        $reason ??= SeededFaker::get()->sentence(4);
        $reactivatedAt ??= Clock::get()->now();

        return $this->withAttributes(['reason' => $reason])
            ->withModifier(static fn (Identity $identity) => $identity->reactivate(Reason::fromString($reason), $reactivatedAt));
    }

    public function erased(?\DateTimeImmutable $erasedAt = null): self
    {
        $erasedAt ??= Clock::get()->now();

        return $this->withModifier(static fn (Identity $identity) => $identity->erase($erasedAt));
    }

    protected function defaults(): array
    {
        return [
            'id' => IdentityId::generate(),
            'registeredAt' => ClockSequence::next(),
        ];
    }

    protected function build(): Identity
    {
        return Identity::register(
            id: $this->attribute('id'),
            registeredAt: $this->attribute('registeredAt'),
        );
    }
}
