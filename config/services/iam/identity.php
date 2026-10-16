<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\BoundedContextServiceLoader;
use Iam\Identity\Domain\Service\PendingIdentityExpiryCalculator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    BoundedContextServiceLoader::load($services, 'Iam\\Identity');

    $container->parameters()->set('iam.identity.pending_expiry', '24 hours');
    $services->get(PendingIdentityExpiryCalculator::class)->arg('$expiry', inline_service(DateInterval::class)->factory([DateInterval::class, 'createFromDateString'])->args(['%iam.identity.pending_expiry%']));
};
