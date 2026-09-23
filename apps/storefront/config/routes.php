<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->import('../src/Controller/', 'attribute');
    $routes->import('security.route_loader.logout', 'service');

    $routes->add('storefront_two_factor_challenge', ['en' => '/2sv', 'fr' => '/v2e'])
        ->controller('scheb_two_factor.form_controller::form')
        ->methods(['GET']);

    $routes->add('storefront_two_factor_challenge_check', ['en' => '/2sv/verify', 'fr' => '/v2e/verifier'])
        ->controller('scheb_two_factor.form_controller::form')
        ->methods(['POST']);
};
