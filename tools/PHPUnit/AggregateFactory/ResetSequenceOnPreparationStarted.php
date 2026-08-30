<?php

declare(strict_types=1);

namespace Tools\PHPUnit\AggregateFactory;

use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use Shared\Tests\Support\Factory\AbstractAggregateTestFactory;

/**
 * Resets the Test Factory creation-instant sequence before each test.
 */
final class ResetSequenceOnPreparationStarted implements PreparationStartedSubscriber
{
    public function notify(PreparationStarted $event): void
    {
        AbstractAggregateTestFactory::resetSequence();
    }
}
