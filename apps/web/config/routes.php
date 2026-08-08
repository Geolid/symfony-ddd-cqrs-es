<?php

declare(strict_types=1);

use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->import('../src/Controller/', 'attribute');
    $routes->import('security.route_loader.logout', 'service');

    $routes->add('web.home', '/')
        ->controller(RedirectController::class)
        ->defaults(['route' => 'sales_order_list', 'permanent' => false])
        ->methods(['GET']);
};
