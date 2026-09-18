<?php

declare(strict_types=1);

namespace Iam\Identity\Application;

enum IdentityModerationStatus: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';

    public function isActive(): bool
    {
        return self::ACTIVE === $this;
    }

    public function isSuspended(): bool
    {
        return self::SUSPENDED === $this;
    }
}
