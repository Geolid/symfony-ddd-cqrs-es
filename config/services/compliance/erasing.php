<?php

declare(strict_types=1);

use Bootstrap\DependencyInjection\BoundedContextServiceLoader;
use Compliance\Erasing\Domain\Service\ErasureRetentionCalculator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->autowire()->autoconfigure();

    BoundedContextServiceLoader::load($services, 'Compliance\\Erasing');

    $container->parameters()->set('compliance.erasing.retention', '30 days');
    $services->get(ErasureRetentionCalculator::class)->arg('$retention', inline_service(DateInterval::class)->factory([DateInterval::class, 'createFromDateString'])->args(['%compliance.erasing.retention%']));
};
