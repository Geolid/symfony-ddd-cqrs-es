<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('api_platform', [
        'title' => 'Ordering & Shipping API',
        'description' => 'Showcase JSON API for the Ordering and Shipping Bounded Contexts.',
        'version' => '1.0.0',
        'show_webby' => false,
        'mapping' => [
            'paths' => ['%kernel.project_dir%/apps/api/src/Resource/'],
        ],
        'defaults' => [
            'pagination_client_enabled' => false,
            'pagination_client_items_per_page' => true,
            'pagination_items_per_page' => 20,
            'pagination_maximum_items_per_page' => 100,
            'normalization_context' => ['skip_null_values' => true],
        ],
        'formats' => [
            'jsonld' => ['application/ld+json'],
            'json' => ['application/json'],
        ],
        'patch_formats' => [
            'json' => ['application/merge-patch+json'],
        ],
    ]);
};
