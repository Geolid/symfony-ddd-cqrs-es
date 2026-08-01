<?php

declare(strict_types=1);

use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->import('@FrameworkBundle/Resources/config/routing/webhook.php')
        ->prefix('/webhooks')
        ->defaults(['_format' => 'json']);

    $routes->add('webhook.home', '/')
        ->controller(RedirectController::class)
        ->defaults(['route' => 'webhook.docs', 'permanent' => false])
        ->methods(['GET']);

    $routes->add('webhook.docs', '/docs')
        ->controller('nelmio_api_doc.controller.swagger_ui')
        ->methods(['GET']);

    $routes->add('webhook.docs_json', '/docs.json')
        ->controller('nelmio_api_doc.controller.swagger_json')
        ->methods(['GET']);
};
