<?php

declare(strict_types=1);

final class MethodNameExempt
{
    public function denormalize(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone('UTC')); // allowed: enclosing method is denormalize()
    }
}
