<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Iam\Identity\Application\Finder\ApiTokenCredential\ApiTokenCredentialFinderInterface;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Identity\Application\Security\ApiTokenCredentialVerifierInterface;
use Iam\Identity\Application\Security\PasswordCredentialVerifierInterface;
use Iam\Identity\Infrastructure\Persistence\Projection\Finder\DbalApiTokenCredentialFinder;
use Iam\Identity\Infrastructure\Persistence\Projection\Finder\DbalIdentityFinder;
use Iam\Identity\Infrastructure\Persistence\Projection\Finder\DbalPasswordCredentialFinder;
use Iam\Identity\Infrastructure\Security\ApiTokenCredentialVerifier;
use Iam\Identity\Infrastructure\Security\PasswordCredentialVerifier;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    SubdomainServiceLoader::load($services, 'Iam');

    // Self-contained — no SecurityBundle dependency, works in apps with no firewall.
    $services->set(NativePasswordHasher::class);

    if ('test' === $container->env()) {
        // Same algorithm, lowest cost — real hashing still runs, just fast.
        $services->get(NativePasswordHasher::class)->arg('$cost', 4);

        // Not otherwise referenced by a service definition; alias+public here or the
        // test container's compiler prunes them.
        $services->alias(PasswordCredentialFinderInterface::class, DbalPasswordCredentialFinder::class)->public();
        $services->alias(ApiTokenCredentialFinderInterface::class, DbalApiTokenCredentialFinder::class)->public();
        $services->alias(IdentityFinderInterface::class, DbalIdentityFinder::class)->public();
        $services->alias(PasswordCredentialVerifierInterface::class, PasswordCredentialVerifier::class)->public();
        $services->alias(ApiTokenCredentialVerifierInterface::class, ApiTokenCredentialVerifier::class)->public();
    }
};
