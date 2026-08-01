<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    if (in_array($routes->env(), ['dev', 'demo'], true)) {
        $routes->import('@PatchlevelEventSourcingAdminBundle/config/routes.yaml')
            ->prefix('/es-admin');
    }
};
