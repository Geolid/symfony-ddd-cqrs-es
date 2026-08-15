<?php

declare(strict_types=1);

use Fulfilment\Shipment\Application\Command\CancelShipment\CancelShipment;
use Fulfilment\Shipment\Application\Command\CreateShipment\CreateShipment;
use Fulfilment\Shipment\Application\Command\DispatchShipment\DispatchShipment;
use Fulfilment\Shipment\Application\Command\ManifestShipment\ManifestShipment;
use Iam\Identity\Application\Command\RevokeApiTokenCredential\RevokeApiTokenCredential;
use Sales\Order\Application\Command\CancelOrder\CancelOrder;
use Sales\Order\Application\Command\CancelOrderPayment\CancelOrderPayment;
use Sales\Order\Application\Command\CaptureOrderPayment\CaptureOrderPayment;
use Sales\Order\Application\Command\CompleteOrder\CompleteOrder;
use Sales\Order\Application\Command\ConfirmOrder\ConfirmOrder;
use Sales\Order\Application\Command\DispatchOrder\DispatchOrder;
use Sales\Order\Application\Command\EraseOrderBillingAddress\EraseOrderBillingAddress;
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
                CancelOrder::class => 'async',
                CancelOrderPayment::class => 'async',
                CancelShipment::class => 'async',
                CaptureOrderPayment::class => 'async',
                CompleteOrder::class => 'async',
                ConfirmOrder::class => 'async',
                CreateShipment::class => 'async',
                DispatchOrder::class => 'async',
                DispatchShipment::class => 'async',
                EraseOrderBillingAddress::class => 'async',
                ManifestShipment::class => 'async',
                RevokeApiTokenCredential::class => 'async',
            ],
        ],
    ]);
};
