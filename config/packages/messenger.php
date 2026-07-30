<?php

declare(strict_types=1);

use Fulfilment\Shipment\Application\Command\CreateShipment\CreateShipment;
use Shared\Application\Command\CommandInterface;
use Shared\Application\Query\QueryInterface;
use Shared\Infrastructure\Messaging\Middleware\DbalTransactionMiddleware;
use Shared\Infrastructure\Monitoring\Sentry\SentryMessengerMiddleware;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'messenger' => [
            'default_bus' => 'command.bus',
            'buses' => [
                'command.bus' => ['middleware' => [SentryMessengerMiddleware::class, DbalTransactionMiddleware::class]],
                'query.bus' => ['middleware' => [SentryMessengerMiddleware::class]],
            ],
            'failure_transport' => 'failed',
            'transports' => [
                'failed' => '%env(resolve:MESSENGER_FAILURE_DSN)%',
                'async' => '%env(resolve:MESSENGER_TRANSPORT_DSN)%',
                'sync' => 'sync://',
            ],
            'routing' => [
                CreateShipment::class => 'async',
                CommandInterface::class => 'sync',
                QueryInterface::class => 'sync',
            ],
        ],
    ]);

    if ('test' === $container->env()) {
        $container->extension('framework', [
            'messenger' => [
                'transports' => [
                    'async' => 'sync://',
                ],
            ],
        ]);
    }
};
