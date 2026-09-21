<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\BoundedContextServiceLoader;
use Iam\Authentication\Application\TotpIssuance\TotpBackupCodeRegenerator;
use Iam\Authentication\Application\TotpIssuance\TotpIssuer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    BoundedContextServiceLoader::load($services, 'Iam\\Authentication');

    // Self-contained — no SecurityBundle dependency, works in apps with no firewall.
    $services->set(NativePasswordHasher::class);

    $container->parameters()->set('iam.authentication.backup_code_count', 5);
    $services->get(TotpIssuer::class)->arg('$backupCodeCount', '%iam.authentication.backup_code_count%');
    $services->get(TotpBackupCodeRegenerator::class)->arg('$backupCodeCount', '%iam.authentication.backup_code_count%');

    if ('test' === $container->env()) {
        // Same algorithm, lowest cost — real hashing still runs, just fast.
        $services->get(NativePasswordHasher::class)->arg('$cost', 4);
    }
};
