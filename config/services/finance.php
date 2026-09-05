<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Finance\Payer\Application\Finder\Payer\PayerFinderInterface;
use Finance\Payer\Infrastructure\Projection\Finder\DbalPayerFinder;
use Finance\Payment\Application\Checkout\PaymentRequester;
use Finance\Payment\Application\Checkout\PaymentRequesterInterface;
use Finance\Payment\Application\Query\ListPaymentsPastReconciliationThreshold\ListPaymentsPastReconciliationThresholdHandler;
use Finance\Payment\Application\Reconciliation\PaymentReconciler;
use Finance\Payment\Application\Reconciliation\PaymentStatusReconcilerInterface;
use Finance\Refund\Application\Finder\Refund\RefundFinderInterface;
use Finance\Refund\Infrastructure\Projection\Finder\DbalRefundFinder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()->set('finance.payment.reconciliation_threshold_minutes', 60);

    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    $services->instanceof(PaymentStatusReconcilerInterface::class)->tag('finance.payment.payment_status_reconciler');

    SubdomainServiceLoader::load($services, 'Finance');

    $services->get(ListPaymentsPastReconciliationThresholdHandler::class)->arg('$thresholdMinutes', '%finance.payment.reconciliation_threshold_minutes%');

    $services->get(PaymentReconciler::class)->arg('$reconcilers', tagged_iterator('finance.payment.payment_status_reconciler'));

    // 2 implementations exist (itself + its decorator) — autowire is ambiguous without this.
    $paymentRequesterAlias = $services->alias(PaymentRequesterInterface::class, PaymentRequester::class);

    if ('test' === $container->env()) {
        // Not otherwise referenced by a service definition; alias+public here or the
        // test container's compiler prunes it.
        $paymentRequesterAlias->public();
        $services->alias(PayerFinderInterface::class, DbalPayerFinder::class)->public();
        $services->alias(RefundFinderInterface::class, DbalRefundFinder::class)->public();
    }
};
