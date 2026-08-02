<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Iam\Access\Infrastructure\Security\GrantVoter;
use Iam\Identity\Application\Port\AuthenticateApiTokenCredentialInterface;
use Iam\Identity\Application\Port\AuthenticatePasswordCredentialInterface;
use Iam\Identity\Infrastructure\Security\ApiTokenCredentialAuthenticationService;
use Iam\Identity\Infrastructure\Security\PasswordCredentialAuthenticationService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    SubdomainServiceLoader::load($services, 'Iam');

    if ('test' === $container->env()) {
        // These #[AsDrivingPort] ports are only ever consumed by a DM's own Authenticator
        // (apps/web, apps/api) — a bare BC-level test container has no such consumer, so the
        // compiler would otherwise prune them as unused.
        $services->alias(AuthenticatePasswordCredentialInterface::class, PasswordCredentialAuthenticationService::class)->public();
        $services->alias(AuthenticateApiTokenCredentialInterface::class, ApiTokenCredentialAuthenticationService::class)->public();
        $services->get(GrantVoter::class)->public();
    }
};
