<?php

declare(strict_types=1);

namespace Shared\Domain\Service;

final readonly class CooldownCalculator
{
    public function __construct(private \DateInterval $cooldown)
    {
    }

    public function retryAt(\DateTimeImmutable $lastRequestedAt): \DateTimeImmutable
    {
        return $lastRequestedAt->add($this->cooldown);
    }
}
