<?php

declare(strict_types=1);

namespace Shared\Domain\Service;

final readonly class CooldownCalculator
{
    public const string DEFAULT_COOLDOWN = '+60 seconds';

    public function __construct(private string $cooldown = self::DEFAULT_COOLDOWN)
    {
    }

    public function retryAt(\DateTimeImmutable $lastRequestedAt): \DateTimeImmutable
    {
        return $lastRequestedAt->modify($this->cooldown);
    }
}
