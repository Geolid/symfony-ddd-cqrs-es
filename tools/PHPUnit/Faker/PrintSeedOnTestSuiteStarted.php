<?php

declare(strict_types=1);

namespace Tools\PHPUnit\Faker;

use PHPUnit\Event\TestSuite\Started;
use PHPUnit\Event\TestSuite\StartedSubscriber;

final class PrintSeedOnTestSuiteStarted implements StartedSubscriber
{
    private static bool $hasPrinted = false;

    public function notify(Started $event): void
    {
        if (self::$hasPrinted) {
            return;
        }

        $seed = SeededFaker::seed();
        fwrite(\STDERR, \sprintf(
            "Faker seed: %d, locale: %s (rerun with FAKER_SEED=%d to reproduce)\n",
            $seed,
            SeededFaker::locale(),
            $seed,
        ));

        self::$hasPrinted = true;
    }
}
