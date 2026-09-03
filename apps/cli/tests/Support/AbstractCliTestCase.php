<?php

declare(strict_types=1);

namespace Cli\Tests\Support;

use Bootstrap\Kernel;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class AbstractCliTestCase extends AbstractIntegrationTestCase
{
    private Application $application;

    protected function setUp(): void
    {
        $this->application = new Application(self::bootKernel());
        $this->application->setAutoExit(false);
    }

    /**
     * @param array{environment?: string, debug?: bool} $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Kernel(
            $options['environment'] ?? 'test',
            $options['debug'] ?? (bool) ($_SERVER['APP_DEBUG'] ?? true),
            'cli',
        );
    }

    protected function tester(): ApplicationTester
    {
        return new ApplicationTester($this->application);
    }
}
