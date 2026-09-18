<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\Specification;

final readonly class PendingIdentityExpiredSpecification
{
    public const int HOURS = 24;

    public function __construct(private \DateTimeImmutable $now)
    {
    }

    public function isSatisfiedBy(\DateTimeImmutable $registeredAt): bool
    {
        return $this->now > $registeredAt->modify(\sprintf('+%d hours', self::HOURS));
    }
}
