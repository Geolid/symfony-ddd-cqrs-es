<?php

declare(strict_types=1);

final class ModifyOffAnchor
{
    public function test(): void
    {
        $anchor = new DateTimeImmutable('2026-01-01T00:00:00+00:00'); // allowed: the anchor itself
        $later = $anchor->modify('+1 day'); // allowed: derived via modify(), not a new instance
    }
}
