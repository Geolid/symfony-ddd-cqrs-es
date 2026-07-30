<?php

declare(strict_types=1);

namespace Support\PHPUnit;

use Bootstrap\Kernel;
use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use PHPUnit\Event\TestSuite\Started;
use PHPUnit\Event\TestSuite\StartedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

final class SetupDatabaseExtension implements Extension, StartedSubscriber
{
    private static bool $hasBeenReset = false;

    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber($this);
    }

    public function notify(Started $event): void
    {
        $testClass = $event->testSuite()->name();

        if (!is_subclass_of($testClass, KernelTestCase::class)) {
            return;
        }

        $this->reset();
    }

    private function reset(): void
    {
        if (self::$hasBeenReset) {
            return;
        }

        StaticDriver::setKeepStaticConnections(false);

        $kernel = new Kernel('test', (bool) ($_SERVER['APP_DEBUG'] ?? true));
        $kernel->boot();

        $application = new Application($kernel);

        $output = new NullOutput();

        $commands = [
            'event-sourcing:database:drop' => ['--force' => true, '--if-exists' => true],
            'doctrine:database:drop' => ['--connection' => 'read_model', '--force' => true, '--if-exists' => true],
            'event-sourcing:database:create' => [],
            'doctrine:database:create' => ['--connection' => 'read_model'],
            'event-sourcing:schema:create' => [],
            'event-sourcing:subscription:setup' => [],
            'event-sourcing:subscription:boot' => [],
        ];

        foreach ($commands as $commandName => $args) {
            $returnCode = $application->doRun(new ArrayInput(['command' => $commandName] + $args), $output);

            if (0 !== $returnCode) {
                throw new \RuntimeException(\sprintf('Command "%s" failed with exit code %d.', $commandName, $returnCode));
            }
        }

        $kernel->shutdown();

        StaticDriver::setKeepStaticConnections(true);

        self::$hasBeenReset = true;
    }
}
