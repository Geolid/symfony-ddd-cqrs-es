<?php

declare(strict_types=1);

namespace Shopping\Cart\Domain\ValueObject;

enum CartState: string
{
    case ACTIVE = 'active';
    case PURCHASED = 'purchased';

    public function isPurchased(): bool
    {
        return self::PURCHASED === $this;
    }
}
