<?php

declare(strict_types=1);

namespace Tools\PHPUnit\Valkey;

use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use Predis\Client;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tools\PHPUnit\KernelTestCaseHelper;

/**
 * Deletes this worker's own Valkey keys before each test, scoped by `valkey.key_prefix`.
 */
final class FlushKeysOnPreparationStarted implements PreparationStartedSubscriber
{
    public function notify(PreparationStarted $event): void
    {
        $test = $event->test();

        if (!$test instanceof TestMethod || !is_subclass_of($test->className(), KernelTestCase::class)) {
            return;
        }

        try {
            $container = KernelTestCaseHelper::getContainer($test->className());
            $client = $container->get('shared.valkey.client');
            \assert($client instanceof Client);

            $prefix = $container->getParameter('valkey.key_prefix');
            \assert(\is_string($prefix));

            $keys = $client->keys($prefix.'*');

            if ([] !== $keys) {
                $client->del($keys);
            }
        } finally {
            KernelTestCaseHelper::ensureKernelShutdown($test->className());
        }
    }
}
