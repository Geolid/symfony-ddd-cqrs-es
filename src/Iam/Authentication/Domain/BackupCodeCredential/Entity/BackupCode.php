<?php

declare(strict_types=1);

namespace Iam\Authentication\Domain\BackupCodeCredential\Entity;

final readonly class BackupCode
{
    public function __construct(
        public string $hashedCode,
        public ?\DateTimeImmutable $consumedAt = null,
    ) {
    }

    public function consumed(\DateTimeImmutable $consumedAt): self
    {
        return new self($this->hashedCode, $consumedAt);
    }

    public function isConsumed(): bool
    {
        return null !== $this->consumedAt;
    }
}
