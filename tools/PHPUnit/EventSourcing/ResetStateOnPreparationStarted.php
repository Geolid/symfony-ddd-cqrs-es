<?php

declare(strict_types=1);

namespace Tools\PHPUnit\EventSourcing;

use Patchlevel\EventSourcing\Store\InMemoryStore;
use Patchlevel\EventSourcingBundle\Subscription\StaticInMemorySubscriptionStoreFactory;
use Patchlevel\Hydrator\Extension\Cryptography\Store\CipherKeyStore;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tools\PHPUnit\KernelTestCaseHelper;

/**
 * Resets in-memory event, subscription and cipher key stores before each test.
 * Ensures data isolation without rebooting the kernel, hooking before setUp().
 */
final class ResetStateOnPreparationStarted implements PreparationStartedSubscriber
{
    public function notify(PreparationStarted $event): void
    {
        $test = $event->test();

        if (!$test instanceof TestMethod || !is_subclass_of($test->className(), KernelTestCase::class)) {
            return;
        }

        try {
            $container = KernelTestCaseHelper::getContainer($test->className());

            $container->get(InMemoryStore::class)->clear();
            $container->get(CipherKeyStore::class)->clear();

            foreach (StaticInMemorySubscriptionStoreFactory::create()->find() as $subscription) {
                $subscription->changePosition(0);
                $subscription->active();
            }
        } finally {
            KernelTestCaseHelper::ensureKernelShutdown($test->className());
        }
    }
}
