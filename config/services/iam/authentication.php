<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\BoundedContextServiceLoader;
use Iam\Authentication\Application\BackupCodeRegeneration\BackupCodeRegenerator;
use Iam\Authentication\Application\TotpEnrollment\TotpEnroller;
use Iam\Authentication\Domain\TrustedDevice\Service\TrustedDeviceExpiryCalculator;
use Symfony\Component\Clock\Clock;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    BoundedContextServiceLoader::load($services, 'Iam\\Authentication');

    $services->set(NativePasswordHasher::class);

    $container->parameters()->set('iam.authentication.backup_code_count', 10);
    $services->get(TotpEnroller::class)->arg('$backupCodeCount', '%iam.authentication.backup_code_count%');
    $services->get(BackupCodeRegenerator::class)->arg('$backupCodeCount', '%iam.authentication.backup_code_count%');

    $container->parameters()->set('iam.authentication.backup_code_digit_count', 8);

    $trustedDeviceExpiry = '30 days';
    $container->parameters()->set('iam.authentication.trusted_device_expiry', $trustedDeviceExpiry);
    $now = Clock::get()->now();
    $container->parameters()->set('iam.authentication.trusted_device_lifetime', $now->add(DateInterval::createFromDateString($trustedDeviceExpiry))->getTimestamp() - $now->getTimestamp());
    $services->get(TrustedDeviceExpiryCalculator::class)->arg('$expiry', inline_service(DateInterval::class)->factory([DateInterval::class, 'createFromDateString'])->args(['%iam.authentication.trusted_device_expiry%']));

    if ('test' === $container->env()) {
        // Same algorithm, lowest cost — real hashing still runs, just fast.
        $services->get(NativePasswordHasher::class)->arg('$cost', 4);
    }
};
