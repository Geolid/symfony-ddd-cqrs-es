<?php

declare(strict_types=1);

namespace Support\PHPUnit\EventSourcing;

use Bootstrap\Kernel;
use PHPUnit\Event\TestSuite\Started;
use PHPUnit\Event\TestSuite\StartedSubscriber;
use Support\PHPUnit\ThrowawayKernelHelper;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Drops and recreates the event sourcing and read model databases once before the test suite starts.
 */
final class ResetDatabasesOnTestSuiteStarted implements StartedSubscriber
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
            $application = new Application($kernel);
            $output = new NullOutput();

            $commands = [
                'event-sourcing:database:drop' => ['--force' => true, '--if-exists' => true],
                'event-sourcing:database:create' => [],
                'doctrine:database:drop' => ['--connection' => 'read_model', '--force' => true, '--if-exists' => true],
                'doctrine:database:create' => ['--connection' => 'read_model'],
            ];

            foreach ($commands as $commandName => $args) {
                $returnCode = $application->doRun(new ArrayInput(['command' => $commandName] + $args), $output);

                if (0 !== $returnCode) {
                    throw new \RuntimeException(\sprintf('Command "%s" failed with exit code %d.', $commandName, $returnCode));
                }
            }
        });

        self::$hasBeenCreated = true;
    }
}
