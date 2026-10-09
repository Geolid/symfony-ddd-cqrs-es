<?php

declare(strict_types=1);

use Support\Foundry\FoundryFaker;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Foundry\Test\UnitTestConfig;

require __DIR__.'/../vendor/autoload.php';

new Dotenv()->bootEnv(__DIR__.'/../.env');

UnitTestConfig::configure(faker: FoundryFaker::create());

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

if (!getenv('CI')) {
    new Filesystem()->remove(__DIR__.'/../var/cache/test');
}
