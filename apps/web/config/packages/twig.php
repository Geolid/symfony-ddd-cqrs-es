<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('twig', [
        'default_path' => '%kernel.project_dir%/apps/web/templates',
        'paths' => [
            '%kernel.project_dir%/apps/web/templates' => null,
            // Shared across every Twig-using DM (today, just this one) — the base layout and
            // pagination/flash macros. CSS/JS themselves are served through AssetMapper
            // (apps/web/config/packages/asset_mapper.php), not this Twig namespace.
            '%kernel.project_dir%/ui/templates' => 'ui',
        ],
    ]);
};
