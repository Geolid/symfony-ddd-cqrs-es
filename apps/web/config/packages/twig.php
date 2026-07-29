<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('twig', [
        'file_name_pattern' => '*.twig',
        'default_path' => '%kernel.project_dir%/apps/web/templates',
        'paths' => [
            '%kernel.project_dir%/apps/web/templates' => null,
            '%kernel.project_dir%/ui/templates' => 'ui',
        ],
    ]);

    if ('test' === $container->env()) {
        $container->extension('twig', [
            'strict_variables' => true,
        ]);
    }
};
