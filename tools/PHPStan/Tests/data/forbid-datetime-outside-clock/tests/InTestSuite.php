<?php

declare(strict_types=1);

final class InTestSuite
{
    public function test(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-01-01T00:00:00+00:00'); // forbidden: message points to Clock::get()->now()
    }
}
