<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\Identity;

use Iam\Identity\Application\IdentityStatus;

final readonly class IdentityResult
{
    public function __construct(
        public string $id,
        public IdentityStatus $status,
        public ?string $reason,
        public \DateTimeImmutable $registeredAt,
        public ?\DateTimeImmutable $suspendedAt,
        public ?\DateTimeImmutable $reactivatedAt,
    ) {
    }
}
