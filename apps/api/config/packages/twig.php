<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('twig', [
        'file_name_pattern' => '*.twig',
        'paths' => [
            '%kernel.project_dir%/ui/templates/bundles/ApiPlatformBundle' => 'ApiPlatform',
        ],
    ]);

    if ('test' === $container->env()) {
        $container->extension('twig', [
            'strict_variables' => true,
        ]);
    }
};
