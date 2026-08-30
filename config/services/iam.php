<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\SubdomainServiceLoader;
use Iam\Authentication\Application\CompromisedPassword\CompromisedPasswordGatewayInterface;
use Iam\Authentication\Application\Finder\ApiKeyCredential\ApiKeyCredentialFinderInterface;
use Iam\Authentication\Application\Finder\PasswordCredential\PasswordCredentialFinderInterface;
use Iam\Authentication\Domain\ApiKeyCredential\Service\ApiKeyHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordHasherInterface;
use Iam\Authentication\Domain\PasswordCredential\Service\PasswordStrengthInterface;
use Iam\Authentication\Infrastructure\ApiKey\NativeApiKeyHasher;
use Iam\Authentication\Infrastructure\CompromisedPassword\SymfonyCompromisedPasswordGateway;
use Iam\Authentication\Infrastructure\Password\SymfonyPasswordHasher;
use Iam\Authentication\Infrastructure\Password\SymfonyPasswordStrength;
use Iam\Authentication\Infrastructure\Projection\Finder\DbalApiKeyCredentialFinder;
use Iam\Authentication\Infrastructure\Projection\Finder\DbalPasswordCredentialFinder;
use Iam\Identity\Application\Finder\Identity\IdentityFinderInterface;
use Iam\Identity\Infrastructure\Projection\Finder\DbalIdentityFinder;
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
        $services->alias(ApiKeyHasherInterface::class, NativeApiKeyHasher::class)->public();
        $services->alias(IdentityFinderInterface::class, DbalIdentityFinder::class)->public();
        $services->alias(PasswordCredentialFinderInterface::class, DbalPasswordCredentialFinder::class)->public();
        $services->alias(PasswordHasherInterface::class, SymfonyPasswordHasher::class)->public();
        $services->alias(PasswordStrengthInterface::class, SymfonyPasswordStrength::class)->public();
        $services->alias(CompromisedPasswordGatewayInterface::class, SymfonyCompromisedPasswordGateway::class)->public();
    }
};
