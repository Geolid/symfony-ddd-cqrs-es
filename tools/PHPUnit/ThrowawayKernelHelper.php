<?php

declare(strict_types=1);

namespace Tools\PHPUnit;

use Bootstrap\Kernel;
use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;

/**
 * Boots a temporary, isolated Kernel for one-off setup tasks outside the test lifecycle.
 * Bypasses test database transactions to ensure independent execution.
 */
final class ThrowawayKernelHelper
{
    public static function run(callable $callback): void
    {
        StaticDriver::setKeepStaticConnections(false);

        $kernel = new Kernel('test', (bool) ($_SERVER['APP_DEBUG'] ?? true));
        $kernel->boot();

        try {
            $callback($kernel);
        } finally {
            $kernel->shutdown();
            StaticDriver::setKeepStaticConnections(true);
        }
    }
}
