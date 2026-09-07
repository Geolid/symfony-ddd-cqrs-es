<?php

declare(strict_types=1);

namespace Compliance\Erasure\Domain\Specification;

final readonly class ErasureRetentionExpiredSpecification
{
    public const int DAYS = 30;

    public function __construct(private \DateTimeImmutable $now)
    {
    }

    public function isSatisfiedBy(\DateTimeImmutable $requestedAt): bool
    {
        return $this->now > $requestedAt->modify(\sprintf('+%d days', self::DAYS));
    }
}
