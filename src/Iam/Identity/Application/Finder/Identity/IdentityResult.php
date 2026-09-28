<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Finder\Identity;

use Iam\Identity\Application\IdentityModerationStatus;
use Iam\Identity\Application\IdentityVerificationStatus;
use Shared\Application\ErasureStatus;

final readonly class IdentityResult
{
    public function __construct(
        public string $id,
        public string $fullName,
        public string $email,
        public IdentityVerificationStatus $verificationStatus,
        public IdentityModerationStatus $moderationStatus,
        public ?string $reason,
        public \DateTimeImmutable $registeredAt,
        public \DateTimeImmutable $confirmationRequestedAt,
        public ?\DateTimeImmutable $suspendedAt,
        public ?\DateTimeImmutable $reactivatedAt,
        public ErasureStatus $erasureStatus,
    ) {
    }

    public function isAuthenticatable(): bool
    {
        return $this->verificationStatus->isConfirmed() && $this->moderationStatus->isActive();
    }
}
