<?php

declare(strict_types=1);

use Fulfilment\Shipment\Application\Command\CancelShipment\CancelShipment;
use Fulfilment\Shipment\Application\Command\CreateShipment\CreateShipment;
use Fulfilment\Shipment\Application\Command\DispatchShipment\DispatchShipment;
use Iam\Identity\Application\Command\RevokeApiTokenCredential\RevokeApiTokenCredential;
use Shared\Infrastructure\Monitoring\Sentry\SentryMessengerMiddleware;
use Shared\Infrastructure\Persistence\Transaction\DbalTransactionMessengerMiddleware;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'messenger' => [
            'default_bus' => 'command.bus',
            'buses' => [
                'command.bus' => ['middleware' => [SentryMessengerMiddleware::class, DbalTransactionMessengerMiddleware::class]],
                'query.bus' => ['middleware' => [SentryMessengerMiddleware::class]],
            ],
            'failure_transport' => 'failed',
            'transports' => [
                'failed' => '%env(resolve:MESSENGER_FAILURE_DSN)%',
                'async' => '%env(resolve:MESSENGER_TRANSPORT_DSN)%',
            ],
            'routing' => [
                CancelShipment::class => 'async',
                CreateShipment::class => 'async',
                DispatchShipment::class => 'async',
                RevokeApiTokenCredential::class => 'async',
            ],
        ],
    ]);
};
