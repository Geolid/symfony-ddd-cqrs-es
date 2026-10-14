<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\BoundedContextServiceLoader;
use Iam\Identity\Domain\Service\PendingIdentityExpiryCalculator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    BoundedContextServiceLoader::load($services, 'Iam\\Identity');

    $container->parameters()->set('iam.identity.pending_expiry_seconds', 24 * 3600);
    $services->get(PendingIdentityExpiryCalculator::class)->arg('$seconds', '%iam.identity.pending_expiry_seconds%');
};
