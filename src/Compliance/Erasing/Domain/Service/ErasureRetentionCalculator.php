<?php

declare(strict_types=1);

namespace Compliance\Erasing\Domain\Service;

final readonly class ErasureRetentionCalculator
{
    public function __construct(private \DateInterval $retention)
    {
    }

    public function effectiveAt(\DateTimeImmutable $requestedAt): \DateTimeImmutable
    {
        return $requestedAt->add($this->retention);
    }
}
