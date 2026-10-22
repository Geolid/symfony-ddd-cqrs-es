<?php

declare(strict_types=1);

namespace Cron\Tests;

use Bootstrap\Kernel;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Webmozart\Assert\Assert;

abstract class AbstractCronTestCase extends AbstractIntegrationTestCase
{
    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = new Application(self::bootKernel());
        $this->application->setAutoExit(false);
    }

    /**
     * @param array<string, mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        $environment = $options['environment'] ?? 'test';
        $debug = $options['debug'] ?? true;

        Assert::string($environment);
        Assert::boolean($debug);

        return new Kernel($environment, $debug, 'cron');
    }

    protected function tester(): ApplicationTester
    {
        return new ApplicationTester($this->application);
    }

    protected function lock(string $commandName): LockInterface
    {
        $store = SemaphoreStore::isSupported() ? new SemaphoreStore() : new FlockStore();
        $lock = new LockFactory($store)->createLock($commandName);
        $lock->acquire();

        return $lock;
    }
}
