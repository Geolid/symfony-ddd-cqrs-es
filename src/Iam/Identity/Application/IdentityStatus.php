<?php

declare(strict_types=1);

namespace Iam\Identity\Application;

enum IdentityStatus: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';

    public function isActive(): bool
    {
        return self::ACTIVE === $this;
    }
}
