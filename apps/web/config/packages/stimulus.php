<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('stimulus', [
        'controller_paths' => [
            '%kernel.project_dir%/ui/assets/controllers',
            '%kernel.project_dir%/apps/web/assets/controllers',
        ],
        'controllers_json' => '%kernel.project_dir%/ui/assets/controllers.json',
    ]);
};
