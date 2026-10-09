<?php

declare(strict_types=1);

namespace Support\Foundry;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\ORM\ResetDatabase\OrmResetter;

/**
 * Replaces Foundry's ORM resetter (decorates it, never calls it): the same sequence as `castor db:reset`,
 * run through the console commands on the kernel Foundry hands in.
 */
final class EventSourcingResetter implements OrmResetter
{
    private const array ALLOWED_ENVIRONMENTS = ['dev', 'demo', 'test'];

    public function resetBeforeFirstTest(KernelInterface $kernel): void
    {
        if (!\in_array($kernel->getEnvironment(), self::ALLOWED_ENVIRONMENTS, true)) {
            throw new \LogicException(\sprintf('Refused: resetting the databases is destructive and only allowed in %s (got "%s").', implode('/', self::ALLOWED_ENVIRONMENTS), $kernel->getEnvironment()));
        }

        // DAMA holds one open connection per process, which would block the database drops
        $keepStaticConnections = class_exists(StaticDriver::class) && StaticDriver::isKeepStaticConnections();
        if ($keepStaticConnections) {
            StaticDriver::setKeepStaticConnections(false);
        }

        try {
            $this->run($kernel);
        } finally {
            if ($keepStaticConnections) {
                StaticDriver::setKeepStaticConnections(true);
            }
        }
    }

    public function resetBeforeEachTest(KernelInterface $kernel): void
    {
    }

    private function run(KernelInterface $kernel): void
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        foreach ([
            ['event-sourcing:database:drop', '--force' => true, '--if-exists' => true],
            ['doctrine:database:drop', '--connection' => 'read_model', '--force' => true, '--if-exists' => true],
            ['event-sourcing:database:create', '--if-not-exists' => true],
            ['doctrine:database:create', '--connection' => 'read_model', '--if-not-exists' => true],
            ['event-sourcing:schema:update', '--force' => true],
            ['event-sourcing:subscription:setup'],
            ['event-sourcing:subscription:boot'],
        ] as $arguments) {
            $name = array_shift($arguments);
            $output = new BufferedOutput();

            if (0 !== $application->run(new ArrayInput(['command' => $name, '--no-interaction' => true] + $arguments), $output)) {
                throw new \RuntimeException(\sprintf('Command "%s" failed: %s', $name, $output->fetch()));
            }
        }
    }
}
