<?php

declare(strict_types=1);

namespace Shared\Domain\Specification;

use Shared\Domain\Service\CooldownCalculator;

final readonly class CooldownElapsedSpecification
{
    public function __construct(
        private CooldownCalculator $calculator,
        private \DateTimeImmutable $now,
    ) {
    }

    public function isSatisfiedBy(?\DateTimeImmutable $lastRequestedAt): bool
    {
        return null === $lastRequestedAt || $this->now >= $this->calculator->retryAt($lastRequestedAt);
    }
}
