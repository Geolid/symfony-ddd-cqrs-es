<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\ValueObject;

enum IdentityState: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case ERASED = 'erased';

    public function isActive(): bool
    {
        return self::ACTIVE === $this;
    }

    public function isSuspended(): bool
    {
        return self::SUSPENDED === $this;
    }

    public function isErased(): bool
    {
        return self::ERASED === $this;
    }
}
