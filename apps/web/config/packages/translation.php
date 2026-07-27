<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'default_locale' => 'en',
        'enabled_locales' => ['en', 'fr'],
        'translator' => [
            'default_path' => '%kernel.project_dir%/ui/translations',
            'fallbacks' => ['en'],
            'paths' => ['%kernel.project_dir%/apps/web/translations'],
        ],
    ]);
};
