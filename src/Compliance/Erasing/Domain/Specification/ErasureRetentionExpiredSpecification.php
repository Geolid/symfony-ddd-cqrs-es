<?php

declare(strict_types=1);

namespace Compliance\Erasing\Domain\Specification;

use Compliance\Erasing\Domain\Service\ErasureRetentionCalculator;

final readonly class ErasureRetentionExpiredSpecification
{
    public function __construct(
        private ErasureRetentionCalculator $calculator,
        private \DateTimeImmutable $now,
    ) {
    }

    public function isSatisfiedBy(\DateTimeImmutable $requestedAt): bool
    {
        return $this->now > $this->calculator->effectiveAt($requestedAt);
    }
}
