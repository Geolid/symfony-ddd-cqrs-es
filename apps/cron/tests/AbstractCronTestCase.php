<?php

declare(strict_types=1);

namespace Cron\Tests;

use Bootstrap\Kernel;
use Support\TestCase\AbstractIntegrationTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Webmozart\Assert\Assert;

abstract class AbstractCronTestCase extends AbstractIntegrationTestCase
{
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
}
