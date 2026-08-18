<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Customer\Infrastructure\Persistence\Projection\Finder\DbalCustomerFinder;
use Sales\Order\Application\Payment\OrderPaymentRequester;
use Sales\Order\Application\Payment\OrderPaymentRequesterInterface;
use Sales\Order\Domain\Service\RetentionPolicy;
use Sales\Order\Domain\Service\ReturnWindowPolicy;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()->set('sales.retention_days', 3650);
    $container->parameters()->set('sales.return_window_days', 14);

    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    SubdomainServiceLoader::load($services, 'Sales');

    $services->get(RetentionPolicy::class)->arg('$days', '%sales.retention_days%');
    $services->get(ReturnWindowPolicy::class)->arg('$days', '%sales.return_window_days%');

    // 2 implementations exist (itself + its decorator) — autowire is ambiguous without this.
    $orderPaymentRequesterAlias = $services->alias(OrderPaymentRequesterInterface::class, OrderPaymentRequester::class);

    if ('test' === $container->env()) {
        // Not otherwise referenced by a service definition; alias+public here or the
        // test container's compiler prunes it.
        $services->alias(CustomerFinderInterface::class, DbalCustomerFinder::class)->public();
        $orderPaymentRequesterAlias->public();
    }
};
