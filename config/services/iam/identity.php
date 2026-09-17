<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\BoundedContextServiceLoader;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Query\ListExpiredPendingIdentities\ListExpiredPendingIdentitiesHandler;
use Iam\Identity\Infrastructure\Projection\Finder\DbalIdentityFinder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()->set('iam.identity.pending_expiry_hours', 24);

    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    BoundedContextServiceLoader::load($services, 'Iam', 'Identity');

    $services->get(ListExpiredPendingIdentitiesHandler::class)->arg('$expiryHours', '%iam.identity.pending_expiry_hours%');

    if ('test' === $container->env()) {
        $services->alias(IdentityFinderInterface::class, DbalIdentityFinder::class)->public();
    }
};
