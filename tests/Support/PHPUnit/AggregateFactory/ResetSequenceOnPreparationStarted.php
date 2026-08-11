<?php

declare(strict_types=1);

namespace Support\PHPUnit\AggregateFactory;

use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use Shared\Tests\Support\Factory\AbstractAggregateTestFactory;

/**
 * Resets aggregate test factory sequences before each test.
 */
final class ResetSequenceOnPreparationStarted implements PreparationStartedSubscriber
{
    public function notify(PreparationStarted $event): void
    {
        AbstractAggregateTestFactory::resetSequence();
    }
}
