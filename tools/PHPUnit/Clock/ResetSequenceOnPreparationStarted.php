<?php

declare(strict_types=1);

namespace Tools\PHPUnit\Clock;

use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use Support\ClockSequence;

/**
 * Resets the Test Factory clock sequence before each test.
 */
final class ResetSequenceOnPreparationStarted implements PreparationStartedSubscriber
{
    public function notify(PreparationStarted $event): void
    {
        ClockSequence::reset();
    }
}
