<?php

declare(strict_types=1);

namespace Support\Foundry;

use Faker\Factory;
use Faker\Generator;
use Support\Faker\CountryCodeFakerProvider;
use Support\Faker\CredentialFakerProvider;

/**
 * SPIKE — single place building the Foundry faker (unit: UnitTestConfig::configure, kernel: faker.service).
 */
final class FoundryFaker
{
    public static function create(): Generator
    {
        $faker = Factory::create(getenv('FAKER_LOCALE') ?: Factory::DEFAULT_LOCALE);
        $faker->addProvider(new CountryCodeFakerProvider($faker));
        $faker->addProvider(new CredentialFakerProvider($faker));

        return $faker;
    }
}
