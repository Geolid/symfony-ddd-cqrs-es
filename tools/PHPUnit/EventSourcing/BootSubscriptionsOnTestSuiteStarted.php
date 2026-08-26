<?php

declare(strict_types=1);

namespace Tools\PHPUnit\EventSourcing;

use Bootstrap\Kernel;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngine;
use PHPUnit\Event\TestSuite\Started;
use PHPUnit\Event\TestSuite\StartedSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tools\PHPUnit\ThrowawayKernelHelper;

/**
 * Sets up and boots event sourcing subscriptions (including projector tables) once before the test suite starts.
 */
final class BootSubscriptionsOnTestSuiteStarted implements StartedSubscriber
{
    private static bool $hasBeenBooted = false;

    public function notify(Started $event): void
    {
        if (!is_subclass_of($event->testSuite()->name(), KernelTestCase::class)) {
            return;
        }

        if (self::$hasBeenBooted) {
            return;
        }

        ThrowawayKernelHelper::run(static function (Kernel $kernel): void {
            $engine = $kernel->getContainer()->get('test.service_container')->get(SubscriptionEngine::class);
            $engine->setup();
            $engine->boot();
        });

        self::$hasBeenBooted = true;
    }
}
