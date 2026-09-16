<?php

declare(strict_types=1);

use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->import('../src/Controller/', 'attribute');
    $routes->import('security.route_loader.logout', 'service');

    $routes->add('storefront.home', '/')
        ->controller(RedirectController::class)
        ->defaults(['route' => 'storefront_account_show', 'permanent' => false])
        ->methods(['GET']);

    $routes->add('storefront_totp_challenge', '/2fa')
        ->controller('scheb_two_factor.form_controller::form')
        ->methods(['GET']);

    // Never reached by its own controller: the TwoFactorAuthenticator intercepts a POST here
    // before routing dispatches — the route only needs to exist for the router to resolve it.
    $routes->add('storefront_totp_challenge_check', '/2fa/verify')
        ->controller('scheb_two_factor.form_controller::form')
        ->methods(['POST']);
};
