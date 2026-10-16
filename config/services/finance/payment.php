<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\BoundedContextServiceLoader;
use Finance\Payment\Application\Query\ListPaymentsPastReconciliationThreshold\ListPaymentsPastReconciliationThresholdHandler;
use Finance\Payment\Application\Reconciliation\PaymentReconciler;
use Finance\Payment\Application\Reconciliation\PaymentStatusReconcilerInterface;
use Finance\Payment\Application\Requesting\PaymentRequester;
use Finance\Payment\Application\Requesting\PaymentRequesterInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()->set('finance.payment.reconciliation_threshold', '1 hour');

    // Must exceed the globex.client HTTP timeout, or a slow-but-successful
    // gateway call could outlive the lock and let a concurrent retry race it.
    $container->parameters()->set('finance.payment.payment_request_lock_ttl', 30); // 30 seconds

    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    $services->instanceof(PaymentStatusReconcilerInterface::class)->tag('finance.payment.payment_status_reconciler');

    BoundedContextServiceLoader::load($services, 'Finance\\Payment');

    $services->get(ListPaymentsPastReconciliationThresholdHandler::class)->arg('$threshold', inline_service(DateInterval::class)->factory([DateInterval::class, 'createFromDateString'])->args(['%finance.payment.reconciliation_threshold%']));

    $services->get(PaymentReconciler::class)->arg('$reconcilers', tagged_iterator('finance.payment.payment_status_reconciler'));

    // 2 implementations exist (itself + its decorator) — autowire is ambiguous without this.
    $paymentRequesterAlias = $services->alias(PaymentRequesterInterface::class, PaymentRequester::class);

    if ('test' === $container->env()) {
        // Not otherwise referenced by a service definition; alias+public here or the
        // test container's compiler prunes it.
        $paymentRequesterAlias->public();
    }
};
