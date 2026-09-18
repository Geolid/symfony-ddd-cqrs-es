<?php

declare(strict_types=1);

namespace Iam\Authentication\Application\Finder\Identity;

use Iam\Authentication\Application\IdentityModerationStatus;
use Iam\Authentication\Application\IdentityVerificationStatus;

final readonly class IdentityResult
{
    public function __construct(
        public string $identityId,
        public string $fullName,
        public string $email,
        public IdentityVerificationStatus $verificationStatus,
        public IdentityModerationStatus $moderationStatus,
    ) {
    }
}
