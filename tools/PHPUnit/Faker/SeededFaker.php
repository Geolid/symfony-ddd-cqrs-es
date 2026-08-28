<?php

declare(strict_types=1);

namespace Tools\PHPUnit\Faker;

use Faker\Factory;
use Faker\Generator;

/**
 * One Faker Generator for the whole suite, seeded from `FAKER_SEED` (a CI run pins and logs it,
 * replay locally with that same value) or a fresh random one otherwise, locale from `FAKER_LOCALE`
 * — both printed so a failure stays reproducible either way. A Test Factory's own `faker()` is a
 * shortcut onto this, never a separate cache — every caller shares this single seeded instance.
 */
final class SeededFaker
{
    private static ?int $seed = null;
    private static ?Generator $generator = null;

    public static function seed(): int
    {
        return self::$seed ??= (int) (getenv('FAKER_SEED') ?: random_int(1, \PHP_INT_MAX));
    }

    public static function locale(): string
    {
        return getenv('FAKER_LOCALE') ?: Factory::DEFAULT_LOCALE;
    }

    public static function get(): Generator
    {
        if (null === self::$generator) {
            self::$generator = Factory::create(self::locale());
            self::$generator->seed(self::seed());
        }

        return self::$generator;
    }
}
