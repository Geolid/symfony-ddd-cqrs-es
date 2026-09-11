<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Cart\ValueObject;

enum CartState: string
{
    case ACTIVE = 'active';
    case PURCHASED = 'purchased';

    public function isActive(): bool
    {
        return self::ACTIVE === $this;
    }

    public function isPurchased(): bool
    {
        return self::PURCHASED === $this;
    }
}
