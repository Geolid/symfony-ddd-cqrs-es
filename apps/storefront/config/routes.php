<?php

declare(strict_types=1);

use Storefront\Controller\SignInController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->import('../src/Controller/', 'attribute');
    $routes->import('security.route_loader.logout', 'service');

    $routes->add('storefront_signin_verify', '/signin/verify')
        ->controller(SignInController::class)
        ->methods(['POST']);

    $routes->add('storefront_two_factor_challenge', '/2fa')
        ->controller('scheb_two_factor.form_controller::form')
        ->methods(['GET']);

    $routes->add('storefront_two_factor_challenge_check', '/2fa/verify')
        ->controller('scheb_two_factor.form_controller::form')
        ->methods(['POST']);
};
