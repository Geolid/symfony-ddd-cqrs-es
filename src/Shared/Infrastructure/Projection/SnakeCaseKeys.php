<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Projection;

use Symfony\Component\String\UnicodeString;

final class SnakeCaseKeys
{
    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<array-key, mixed>
     */
    public static function from(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[new UnicodeString($key)->snake()->toString()] = \is_array($value) ? self::from($value) : $value;
        }

        return $result;
    }
}
