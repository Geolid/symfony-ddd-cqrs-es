<?php

declare(strict_types=1);

use Fulfilment\Shipment\Application\Command\PrepareShipment\PrepareShipment;
use Sales\Order\Application\Command\AnonymizeExpiredOrder\AnonymizeExpiredOrder;
use Sales\Order\Application\Command\CancelOrphanedOrder\CancelOrphanedOrder;
use Shared\Infrastructure\Doctrine\Dbal\TransactionMessengerMiddleware;
use Shared\Infrastructure\Sentry\ErrorContextMessengerMiddleware;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'messenger' => [
            'default_bus' => 'command.bus',
            'buses' => [
                'command.bus' => ['middleware' => [ErrorContextMessengerMiddleware::class, TransactionMessengerMiddleware::class]],
                'query.bus' => ['middleware' => [ErrorContextMessengerMiddleware::class]],
            ],
            'failure_transport' => 'failed',
            'transports' => [
                'failed' => '%env(resolve:MESSENGER_FAILURE_DSN)%',
                'async' => '%env(resolve:MESSENGER_TRANSPORT_DSN)%',
            ],
            'routing' => [
                AnonymizeExpiredOrder::class => 'async',
                CancelOrphanedOrder::class => 'async',
                PrepareShipment::class => 'async',
            ],
        ],
    ]);
};
