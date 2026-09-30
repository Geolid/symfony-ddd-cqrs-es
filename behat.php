<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Profile;
use Behat\Config\Suite;
use FriendsOfBehat\SymfonyExtension\ServiceContainer\SymfonyExtension;
use Storefront\Tests\Behat\Kernel;
use Storefront\Tests\Behat\SignInContext;

return new Config()
    ->withProfile(
        new Profile('default')
            ->withExtension(new Extension(SymfonyExtension::class, [
                'bootstrap' => 'apps/storefront/tests/Behat/bootstrap.php',
                'kernel' => [
                    'class' => Kernel::class,
                    'environment' => 'e2e',
                ],
            ]))
            ->withSuite(
                new Suite('storefront')
                    ->withPaths('apps/storefront/tests/Behat/features')
                    ->withContexts(SignInContext::class),
            ),
    );
