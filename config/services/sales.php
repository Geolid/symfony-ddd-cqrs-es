<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Sales\Customer\Application\Finder\Customer\CustomerFinderInterface;
use Sales\Customer\Infrastructure\Persistence\Projection\Finder\DbalCustomerFinder;
use Sales\Order\Application\Payment\OrderPaymentReconciler;
use Sales\Order\Application\Payment\OrderPaymentRequester;
use Sales\Order\Application\Payment\OrderPaymentRequesterInterface;
use Sales\Order\Application\Payment\OrderPaymentStatusReconcilerInterface;
use Sales\Order\Application\Query\ListOrderPaymentsPastReconciliationThreshold\ListOrderPaymentsPastReconciliationThresholdHandler;
use Sales\Order\Domain\Service\RetentionWindow;
use Sales\Order\Domain\Service\ReturnWindow;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()->set('sales.retention_days', 3650);
    $container->parameters()->set('sales.return_window_days', 14);
    $container->parameters()->set('sales.order_payment.reconciliation_threshold_minutes', 60);

    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    $services->instanceof(OrderPaymentStatusReconcilerInterface::class)->tag('sales.order_payment_status_reconciler');

    SubdomainServiceLoader::load($services, 'Sales');

    $services->get(RetentionWindow::class)->arg('$days', '%sales.retention_days%');
    $services->get(ReturnWindow::class)->arg('$days', '%sales.return_window_days%');
    $services->get(ListOrderPaymentsPastReconciliationThresholdHandler::class)->arg('$thresholdMinutes', '%sales.order_payment.reconciliation_threshold_minutes%');

    $services->get(OrderPaymentReconciler::class)->arg('$reconcilers', tagged_iterator('sales.order_payment_status_reconciler'));

    // 2 implementations exist (itself + its decorator) — autowire is ambiguous without this.
    $orderPaymentRequesterAlias = $services->alias(OrderPaymentRequesterInterface::class, OrderPaymentRequester::class);

    if ('test' === $container->env()) {
        // Not otherwise referenced by a service definition; alias+public here or the
        // test container's compiler prunes it.
        $services->alias(CustomerFinderInterface::class, DbalCustomerFinder::class)->public();
        $orderPaymentRequesterAlias->public();
    }
};
