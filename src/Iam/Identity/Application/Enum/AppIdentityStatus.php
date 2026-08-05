<?php

declare(strict_types=1);

namespace Iam\Identity\Application\Enum;

enum AppIdentityStatus: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';

    public function isActive(): bool
    {
        return self::ACTIVE === $this;
    }
}
