<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Iam\Identity\Application\Security\AuthenticateApiTokenCredentialInterface;
use Iam\Identity\Application\Security\AuthenticatePasswordCredentialInterface;
use Iam\Identity\Application\Security\IssueApiTokenCredentialInterface;
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
        $services->alias(AuthenticatePasswordCredentialInterface::class, PasswordCredentialAuthenticationService::class)->public();
        $services->alias(AuthenticateApiTokenCredentialInterface::class, ApiTokenCredentialAuthenticationService::class)->public();
        $services->alias(IssueApiTokenCredentialInterface::class, ApiTokenCredentialIssuingService::class)->public();
        $services->get(IdentityStatusUserChecker::class)->public();
    }
};
