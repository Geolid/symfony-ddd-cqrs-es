<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Validator\Constraints\Email;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'validation' => [
            'email_validation_mode' => Email::VALIDATION_MODE_STRICT,
        ],
    ]);

    if ('test' === $container->env()) {
        $container->extension('framework', [
            'validation' => [
                'not_compromised_password' => false,
            ],
        ]);
    }
};
