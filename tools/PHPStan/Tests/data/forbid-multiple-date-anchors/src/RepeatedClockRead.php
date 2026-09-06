<?php

declare(strict_types=1);

use Symfony\Component\Clock\Clock;

final class RepeatedClockRead
{
    public function test(): void
    {
        $anchor = Clock::get()->now(); // allowed: the anchor itself
        $later = Clock::get()->now()->modify('+1 day'); // forbidden: a second independent Clock::get()->now() call
    }
}
