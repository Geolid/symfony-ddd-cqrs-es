<?php

declare(strict_types=1);

namespace Tools\Faker;

use Faker\Factory;
use Faker\Generator;

/**
 * One Faker Generator for the whole suite — seed from `FAKER_SEED`, locale from `FAKER_LOCALE`,
 * both printed on suite start so a failure stays reproducible.
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
