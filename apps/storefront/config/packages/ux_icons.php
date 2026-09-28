<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('ux_icons', [
        'icon_dir' => '%kernel.project_dir%/ui/assets/icons',
        // Local icons are stroke-based (fill="none"); the bundle's own default (fill:
        // currentColor) would override that and paint their shapes solid.
        'default_icon_attributes' => ['fill' => 'none'],
    ]);
};
