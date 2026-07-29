<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    if (in_array($container->env(), ['dev', 'test'], true)) {
        $container->extension('debug', [
            'dump_destination' => 'tcp://%env(VAR_DUMPER_SERVER)%',
        ]);
    }
};
