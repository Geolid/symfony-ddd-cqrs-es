<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('twig', [
        'default_path' => '%kernel.project_dir%/apps/web/templates',
        'paths' => [
            '%kernel.project_dir%/apps/web/templates' => null,
            // Shared across every Twig-using DM (today, just this one) — the base layout,
            // pagination/flash macros, and the CSS inlined via source() in ui/templates/base.html.twig.
            '%kernel.project_dir%/ui/templates' => 'ui',
            '%kernel.project_dir%/ui/assets' => 'ui_assets',
        ],
    ]);
};
