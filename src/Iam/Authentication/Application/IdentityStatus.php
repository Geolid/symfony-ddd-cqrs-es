<?php

declare(strict_types=1);

namespace Iam\Authentication\Application;

enum IdentityStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';

    public function isActive(): bool
    {
        return self::ACTIVE === $this;
    }
}
