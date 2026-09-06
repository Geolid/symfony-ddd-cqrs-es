<?php

declare(strict_types=1);

final class TwoInstances
{
    public function test(): void
    {
        $anchor = new DateTimeImmutable('2026-01-01T00:00:00+00:00'); // allowed: the first instance is the anchor
        $later = new DateTimeImmutable('2026-01-02T00:00:00+00:00'); // forbidden: second independent instance
    }
}
