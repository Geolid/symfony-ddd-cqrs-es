<?php

declare(strict_types=1);

final class NotDomain
{
    public function test(): void
    {
        $anchor = new DateTimeImmutable('2026-01-01T00:00:00+00:00'); // allowed: this rule only fires under /Domain/
        $later = new DateTimeImmutable('2026-01-02T00:00:00+00:00'); // allowed: ForbidDateTimeImmutableOutsideClockRule owns this zone instead
    }
}
