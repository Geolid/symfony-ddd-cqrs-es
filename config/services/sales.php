<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Sales\Buyer\Application\Finder\Buyer\BuyerFinderInterface;
use Sales\Buyer\Infrastructure\Projection\Finder\DbalBuyerFinder;
use Sales\Order\Application\Payment\OrderPaymentRequester;
use Sales\Order\Application\Payment\OrderPaymentRequesterInterface;
use Sales\Order\Application\Payment\Reconciliation\OrderPaymentReconciler;
use Sales\Order\Application\Payment\Reconciliation\OrderPaymentStatusReconcilerInterface;
use Sales\Order\Application\Query\ListOrderPaymentsPastReconciliationThreshold\ListOrderPaymentsPastReconciliationThresholdHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()->set('sales.order_payment.reconciliation_threshold_minutes', 60);

    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    $services->instanceof(OrderPaymentStatusReconcilerInterface::class)->tag('sales.order.payment_status_reconciler');

    SubdomainServiceLoader::load($services, 'Sales');

    $services->get(ListOrderPaymentsPastReconciliationThresholdHandler::class)->arg('$thresholdMinutes', '%sales.order_payment.reconciliation_threshold_minutes%');

    $services->get(OrderPaymentReconciler::class)->arg('$reconcilers', tagged_iterator('sales.order.payment_status_reconciler'));

    // 2 implementations exist (itself + its decorator) — autowire is ambiguous without this.
    $orderPaymentRequesterAlias = $services->alias(OrderPaymentRequesterInterface::class, OrderPaymentRequester::class);

    if ('test' === $container->env()) {
        // Not otherwise referenced by a service definition; alias+public here or the
        // test container's compiler prunes it.
        $services->alias(BuyerFinderInterface::class, DbalBuyerFinder::class)->public();
        $orderPaymentRequesterAlias->public();
    }
};
