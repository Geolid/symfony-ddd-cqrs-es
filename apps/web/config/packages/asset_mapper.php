<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'asset_mapper' => [
            'public_prefix' => '/assets/web/',
            'missing_import_mode' => 'strict',
            'paths' => [
                '%kernel.project_dir%/ui/assets/' => 'shared',
            ],
            'vendor_dir' => '%kernel.project_dir%/ui/assets/vendor',
            'importmap_path' => '%kernel.project_dir%/apps/web/importmap.php',
        ],
    ]);

    if ('prod' === $container->env()) {
        $container->extension('framework', [
            'asset_mapper' => [
                'missing_import_mode' => 'warn',
            ],
        ]);
    }
};
