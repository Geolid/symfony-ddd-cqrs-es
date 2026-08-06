<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Iam\Identity\Application\Security\ApiTokenCredentialAuthenticatorInterface;
use Iam\Identity\Application\Security\ApiTokenCredentialIssuerInterface;
use Iam\Identity\Application\Security\PasswordCredentialAuthenticatorInterface;
use Iam\Identity\Infrastructure\Security\ApiTokenCredentialAuthenticationService;
use Iam\Identity\Infrastructure\Security\ApiTokenCredentialIssuingService;
use Iam\Identity\Infrastructure\Security\IdentityStatusUserChecker;
use Iam\Identity\Infrastructure\Security\PasswordCredentialAuthenticationService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    SubdomainServiceLoader::load($services, 'Iam');

    if ('test' === $container->env()) {
        // Only consumed by apps/web|api|cli; alias+public here or the test container's compiler prunes them.
        $services->alias(PasswordCredentialAuthenticatorInterface::class, PasswordCredentialAuthenticationService::class)->public();
        $services->alias(ApiTokenCredentialAuthenticatorInterface::class, ApiTokenCredentialAuthenticationService::class)->public();
        $services->alias(ApiTokenCredentialIssuerInterface::class, ApiTokenCredentialIssuingService::class)->public();
        $services->get(IdentityStatusUserChecker::class)->public();
    }
};
