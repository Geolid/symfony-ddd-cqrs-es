<?php

declare(strict_types=1);

use Shared\Application\Command\CommandInterface;
use Shared\Application\Query\QueryInterface;
use Shared\Infrastructure\Messaging\Middleware\DbalTransactionMiddleware;
use Shared\Infrastructure\Monitoring\Sentry\SentryMessengerMiddleware;
use Shipping\Shipment\Application\Command\CreateShipment\CreateShipment;
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
                // A free, self-hostable Doctrine-backed queue. Swap the DSN for
                // redis://, amqp:// (RabbitMQ) or another Messenger transport for higher
                // throughput — no code above the transport layer has to change.
                'async' => '%env(resolve:MESSENGER_TRANSPORT_DSN)%',
                'sync' => 'sync://',
            ],
            'routing' => [
                // Reacting to an Integration Event should never block the publisher's request.
                CreateShipment::class => 'async',
                CommandInterface::class => 'sync',
                QueryInterface::class => 'sync',
            ],
        ],
    ]);
};
