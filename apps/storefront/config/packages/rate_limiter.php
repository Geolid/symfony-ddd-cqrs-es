<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'cache' => [
            'pools' => [
                'cache.rate_limiter' => [
                    'adapter' => 'cache.adapter.redis',
                    'provider' => '%env(VALKEY_URL)%',
                ],
            ],
        ],
        'rate_limiter' => [
            'limiters' => [
                'verification_code_resend_ip' => [
                    'policy' => 'sliding_window',
                    'limit' => 25,
                    'interval' => '15 minutes',
                ],
                'verification_code_resend_identity' => [
                    'policy' => 'sliding_window',
                    'limit' => 5,
                    'interval' => '15 minutes',
                ],
            ],
        ],
    ]);
};
