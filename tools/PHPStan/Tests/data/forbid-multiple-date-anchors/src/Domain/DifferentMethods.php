<?php

declare(strict_types=1);

final class DifferentMethods
{
    public function first(): void
    {
        $anchor = new DateTimeImmutable('2026-01-01T00:00:00+00:00'); // allowed: one anchor per method
    }

    public function second(): void
    {
        $anchor = new DateTimeImmutable('2026-01-02T00:00:00+00:00'); // allowed: one anchor per method
    }
}
