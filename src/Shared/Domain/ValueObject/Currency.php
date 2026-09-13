<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

enum Currency: string
{
    case EUR = 'EUR';
    case GBP = 'GBP';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
