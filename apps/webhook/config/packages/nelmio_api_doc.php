<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('nelmio_api_doc', [
        'documentation' => [
            'info' => [
                'title' => 'Webhook API',
                'description' => 'Inbound webhooks reporting a shipment as delivered or a payment as captured.',
                'version' => '1.0.0',
            ],
            'servers' => [
                ['url' => '/%kernel.app_id%'],
            ],
        ],
        // Endpoints are described programmatically (see WebhookDescriber); route
        // introspection is scoped to the doc route, which carries no OpenAPI metadata.
        'areas' => [
            'default' => [
                'path_patterns' => ['^/docs'],
            ],
        ],
    ]);
};
