<?php

declare(strict_types=1);

final class OneInstanceTest
{
    public function test(): void
    {
        $anchor = new DateTimeImmutable('2026-01-01T00:00:00+00:00'); // allowed: the anchor itself
    }
}

final class ModifyOffAnchorTest
{
    public function test(): void
    {
        $anchor = new DateTimeImmutable('2026-01-01T00:00:00+00:00'); // allowed: the anchor itself
        $later = $anchor->modify('+1 day'); // allowed: derived via modify(), not a new instance
    }
}

final class DifferentMethodsTest
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

final class TwoInstancesTest
{
    public function test(): void
    {
        $anchor = new DateTimeImmutable('2026-01-01T00:00:00+00:00'); // allowed: the first instance is the anchor
        $later = new DateTimeImmutable('2026-01-02T00:00:00+00:00'); // forbidden: second independent instance
    }
}
