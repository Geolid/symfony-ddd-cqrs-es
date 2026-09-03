<?php

declare(strict_types=1);

final class InDomain
{
    public function test(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-01-01T00:00:00+00:00'); // allowed: path contains /Domain/
    }
}
