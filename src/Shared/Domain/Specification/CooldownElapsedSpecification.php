<?php

declare(strict_types=1);

namespace Shared\Domain\Specification;

final readonly class CooldownElapsedSpecification
{
    public function __construct(
        private string $cooldown,
        private \DateTimeImmutable $now,
    ) {
    }

    public function isSatisfiedBy(?\DateTimeImmutable $lastRequestedAt): bool
    {
        return null === $lastRequestedAt || $this->now >= $lastRequestedAt->modify($this->cooldown);
    }
}
