<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\BoundedContextServiceLoader;
use Iam\Authentication\Application\BackupCodeRegeneration\BackupCodeRegenerator;
use Iam\Authentication\Application\TotpEnrollment\TotpEnroller;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    BoundedContextServiceLoader::load($services, 'Iam\\Authentication');

    // Self-contained — no SecurityBundle dependency, works in apps with no firewall.
    $services->set(NativePasswordHasher::class);

    $container->parameters()->set('iam.authentication.backup_code_count', 10);
    $services->get(TotpEnroller::class)->arg('$backupCodeCount', '%iam.authentication.backup_code_count%');
    $services->get(BackupCodeRegenerator::class)->arg('$backupCodeCount', '%iam.authentication.backup_code_count%');

    $container->parameters()->set('iam.authentication.backup_code_digit_count', 8);

    // Seconds — matches scheb_two_factor.php's own trusted_device.lifetime unit.
    $container->parameters()->set('iam.authentication.trusted_device_lifetime', 2592000);

    if ('test' === $container->env()) {
        // Same algorithm, lowest cost — real hashing still runs, just fast.
        $services->get(NativePasswordHasher::class)->arg('$cost', 4);
    }
};
