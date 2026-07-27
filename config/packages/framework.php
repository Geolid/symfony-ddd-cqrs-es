<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'secret' => '%env(APP_SECRET)%',
        'handle_all_throwables' => true,
        'php_errors' => ['log' => true],
        'http_method_override' => false,
        'trust_x_sendfile_type_header' => true,
    ]);

    if ('test' === $container->env()) {
        $container->extension('framework', [
            'test' => true,
        ]);
    }
};
