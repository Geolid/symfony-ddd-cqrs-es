<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Iam\Authentication\Application\Credential\CompromisedPasswordGatewayInterface;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Authentication\Infrastructure\Credential\CompromisedPasswordGateway;
use Iam\Authentication\Infrastructure\Persistence\Projection\Finder\DbalApiKeyCredentialFinder;
use Iam\Authentication\Infrastructure\Persistence\Projection\Finder\DbalPasswordCredentialFinder;
use Iam\Authentication\Infrastructure\Security\ApiKeyHasher;
use Iam\Authentication\Infrastructure\Security\PasswordHasher;
use Iam\Authentication\Infrastructure\Security\PasswordStrength;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Infrastructure\Persistence\Projection\Finder\DbalIdentityFinder;
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
        $services->alias(ApiKeyCredentialFinderInterface::class, DbalApiKeyCredentialFinder::class)->public();
        $services->alias(ApiKeyHasherInterface::class, ApiKeyHasher::class)->public();
        $services->alias(IdentityFinderInterface::class, DbalIdentityFinder::class)->public();
        $services->alias(PasswordCredentialFinderInterface::class, DbalPasswordCredentialFinder::class)->public();
        $services->alias(PasswordHasherInterface::class, PasswordHasher::class)->public();
        $services->alias(PasswordStrengthInterface::class, PasswordStrength::class)->public();
        $services->alias(CompromisedPasswordGatewayInterface::class, CompromisedPasswordGateway::class)->public();
    }
};
