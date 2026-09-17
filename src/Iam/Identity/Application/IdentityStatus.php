<?php

declare(strict_types=1);

namespace Iam\Identity\Application;

enum IdentityStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';

    public function isPending(): bool
    {
        return self::PENDING === $this;
    }

    public function isActive(): bool
    {
        return self::ACTIVE === $this;
    }
}
