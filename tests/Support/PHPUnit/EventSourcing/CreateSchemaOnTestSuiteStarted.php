<?php

declare(strict_types=1);

namespace Support\PHPUnit\EventSourcing;

use Bootstrap\Kernel;
use Patchlevel\EventSourcing\Schema\SchemaDirector;
use PHPUnit\Event\TestSuite\Started;
use PHPUnit\Event\TestSuite\StartedSubscriber;
use Support\PHPUnit\ThrowawayKernelHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Creates the event sourcing schema once before the test suite starts.
 */
final class CreateSchemaOnTestSuiteStarted implements StartedSubscriber
{
    private static bool $hasBeenCreated = false;

    public function notify(Started $event): void
    {
        if (!is_subclass_of($event->testSuite()->name(), KernelTestCase::class)) {
            return;
        }

        if (self::$hasBeenCreated) {
            return;
        }

        ThrowawayKernelHelper::run(static function (Kernel $kernel): void {
            $kernel->getContainer()->get('test.service_container')->get(SchemaDirector::class)->create();
        });

        self::$hasBeenCreated = true;
    }
}
