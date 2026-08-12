<?php

declare(strict_types=1);

namespace Support\PHPUnit\EventSourcing;

use Patchlevel\EventSourcing\Store\InMemoryStore;
use Patchlevel\EventSourcingBundle\Subscription\StaticInMemorySubscriptionStoreFactory;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use Support\Helpers\KernelTestCaseHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Resets in-memory event and subscription stores before each test.
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
            KernelTestCaseHelper::getContainer($test->className())->get(InMemoryStore::class)->clear();

            foreach (StaticInMemorySubscriptionStoreFactory::create()->find() as $subscription) {
                $subscription->changePosition(0);
                $subscription->active();
            }
        } finally {
            KernelTestCaseHelper::ensureKernelShutdown($test->className());
        }
    }
}
