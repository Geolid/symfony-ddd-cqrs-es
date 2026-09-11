<?php

declare(strict_types=1);

namespace Shopping\Checkout\Domain\Cart\ValueObject;

enum CartState: string
{
    case ACTIVE = 'active';
    case CHECKOUT = 'checkout';
    case CONVERTED = 'converted';

    public function isActive(): bool
    {
        return self::ACTIVE === $this;
    }

    public function isCheckout(): bool
    {
        return self::CHECKOUT === $this;
    }

    public function isConverted(): bool
    {
        return self::CONVERTED === $this;
    }
}
