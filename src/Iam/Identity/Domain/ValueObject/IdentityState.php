<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\ValueObject;

enum IdentityState: string
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

    public function isSuspended(): bool
    {
        return self::SUSPENDED === $this;
    }
}
