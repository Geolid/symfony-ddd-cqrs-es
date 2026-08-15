<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Translation\IdentityTranslator;

return static function (ContainerConfigurator $container): void {
    if ('demo' === $container->env()) {
        $container->services()
            ->defaults()->autowire()->autoconfigure()
            ->load('Demo\\', '%kernel.project_dir%/demo/**/*{Command}.php');
    }

    if ('test' === $container->env()) {
        // A test asserts the stable message key, never the translated wording.
        $container->services()->set('translator', IdentityTranslator::class)->public();
    }
};
