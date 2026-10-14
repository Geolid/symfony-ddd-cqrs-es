<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\BoundedContextServiceLoader;
use Compliance\Erasing\Domain\Service\ErasureRetentionCalculator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    BoundedContextServiceLoader::load($services, 'Compliance\\Erasing');

    $container->parameters()->set('compliance.erasing.retention_seconds', 30 * 24 * 60 * 60);
    $services->get(ErasureRetentionCalculator::class)->arg('$seconds', '%compliance.erasing.retention_seconds%');
};
