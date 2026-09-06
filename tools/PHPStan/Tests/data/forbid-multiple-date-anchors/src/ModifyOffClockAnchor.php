<?php

declare(strict_types=1);

use Symfony\Component\Clock\Clock;

final class ModifyOffClockAnchor
{
    public function test(): void
    {
        $anchor = Clock::get()->now(); // allowed: the anchor itself
        $later = $anchor->modify('+1 day'); // allowed: derived off the anchor variable, not a new Clock::get() call
    }
}
