<?php

declare(strict_types=1);

namespace AfterSales\Return\Domain\Specification;

final readonly class WithdrawalWindowExpiredSpecification
{
    public const int DAYS = 14;

    public function __construct(private \DateTimeImmutable $now)
    {
    }

    public function isSatisfiedBy(\DateTimeImmutable $deliveredAt): bool
    {
        return $this->now > $deliveredAt->modify(\sprintf('+%d days', self::DAYS));
    }
}
