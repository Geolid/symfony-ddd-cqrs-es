<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\Identity;

use Iam\Identity\Application\IdentityStatus;
use Shared\Application\ErasureStatus;

final readonly class IdentityResult
{
    public function __construct(
        public string $id,
        public string $fullName,
        public string $email,
        public IdentityStatus $status,
        public ?string $reason,
        public \DateTimeImmutable $registeredAt,
        public ?\DateTimeImmutable $emailConfirmationResendRequestedAt,
        public ?\DateTimeImmutable $suspendedAt,
        public ?\DateTimeImmutable $reactivatedAt,
        public ErasureStatus $erasureStatus,
    ) {
    }
}
