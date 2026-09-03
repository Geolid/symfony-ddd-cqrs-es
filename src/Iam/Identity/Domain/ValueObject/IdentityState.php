<?php

declare(strict_types=1);

namespace Iam\Identity\Domain\ValueObject;

enum IdentityState: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case ERASED = 'erased';

    /**
     * @phpstan-pure
     */
    public function isActive(): bool
    {
        return self::ACTIVE === $this;
    }

    /**
     * @phpstan-pure
     */
    public function isSuspended(): bool
    {
        return self::SUSPENDED === $this;
    }

    /**
     * @phpstan-pure
     */
    public function isErased(): bool
    {
        return self::ERASED === $this;
    }
}
