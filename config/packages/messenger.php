<?php

declare(strict_types=1);

use Compliance\Erasing\Application\Command\ApproveErasure\ApproveErasure;
use Fulfilment\Shipping\Application\Command\ApproveShipmentErasure\ApproveShipmentErasure;
use Iam\Authentication\Application\Command\RevokeTrustedDevice\RevokeTrustedDevice;
use Iam\Identity\Application\Command\EraseIdentity\EraseIdentity;
use Iam\Identity\Application\Command\ErasePendingIdentity\ErasePendingIdentity;
use Sales\Ordering\Application\Command\ApproveOrderErasure\ApproveOrderErasure;
use Shared\Infrastructure\Doctrine\Dbal\TransactionMessengerMiddleware;
use Shared\Infrastructure\Sentry\ErrorContextMessengerMiddleware;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;

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
                ApproveErasure::class => 'async',
                ApproveOrderErasure::class => 'async',
                ApproveShipmentErasure::class => 'async',
                EraseIdentity::class => 'async',
                ErasePendingIdentity::class => 'async',
                RevokeTrustedDevice::class => 'async',
                SendEmailMessage::class => 'async',
            ],
        ],
    ]);
};
