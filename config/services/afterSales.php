<?php

declare(strict_types=1);

use AfterSales\Return\Application\Withdrawal\CanRequestWithdrawalChecker;
use AfterSales\Return\Application\Withdrawal\CanRequestWithdrawalInterface;
use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    SubdomainServiceLoader::load($services, 'AfterSales');

    if ('test' === $container->env()) {
        // Not otherwise referenced by a service definition; alias+public here or the
        // test container's compiler prunes it.
        $services->alias(CanRequestWithdrawalInterface::class, CanRequestWithdrawalChecker::class)->public();
    }
};
