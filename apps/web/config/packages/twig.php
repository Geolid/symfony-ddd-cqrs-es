<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('twig', [
        'default_path' => '%kernel.project_dir%/apps/web/templates',
        'paths' => [
            '%kernel.project_dir%/apps/web/templates' => null,
        ],
    ]);
};
