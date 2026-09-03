<?php

declare(strict_types=1);

final class RawRow
{
    /**
     * @param array<string, string> $row
     */
    public function test(array $row): DateTimeImmutable
    {
        return new DateTimeImmutable($row['value'], new DateTimeZone('UTC')); // allowed: rehydrating a raw row
    }
}
