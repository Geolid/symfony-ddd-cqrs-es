<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

enum CountryCode: string
{
    case DE = 'DE';
    case FR = 'FR';
    case IT = 'IT';

    /**
     * ISO 3166-1 user-assigned code, never allocated to a real country — used as the GDPR-erasure sentinel.
     */
    case ZZ = 'ZZ';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
