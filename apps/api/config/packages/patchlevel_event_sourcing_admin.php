<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    if (in_array($container->env(), ['dev', 'demo'], true)) {
        $container->extension('patchlevel_event_sourcing_admin', [
            'enabled' => true,
        ]);
    }
};
