<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Webhook\OpenApi\WebhookDescriber;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->defaults()->autowire()->autoconfigure()
        ->load('Webhook\\', '../src/');

    // Nelmio collects describers per area via a dedicated tag, not the DescriberInterface autoconfiguration.
    $services->get(WebhookDescriber::class)
        ->tag('nelmio_api_doc.describer.default');
};
