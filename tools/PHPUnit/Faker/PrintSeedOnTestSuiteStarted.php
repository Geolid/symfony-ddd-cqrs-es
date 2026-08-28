<?php

declare(strict_types=1);

namespace Tools\PHPUnit\Faker;

use PHPUnit\Event\TestSuite\Started;
use PHPUnit\Event\TestSuite\StartedSubscriber;
use Tools\Faker\SeededFaker;

final class PrintSeedOnTestSuiteStarted implements StartedSubscriber
{
    private static bool $hasPrinted = false;

    public function notify(Started $event): void
    {
        if (self::$hasPrinted) {
            return;
        }

        $seed = SeededFaker::seed();
        $locale = SeededFaker::locale();
        fwrite(\STDERR, \sprintf(
            "Faker seed: %d, locale: %s (rerun with FAKER_SEED=%d FAKER_LOCALE=%s to reproduce)\n",
            $seed,
            $locale,
            $seed,
            $locale,
        ));

        self::$hasPrinted = true;
    }
}
