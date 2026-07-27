<?php

declare(strict_types=1);

use Ordering\Order\Domain\Exception\OrderAlreadyCancelledException;
use Ordering\Order\Domain\Exception\OrderNotFoundException;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shipping\Shipment\Domain\Exception\InvalidShipmentTransitionException;
use Shipping\Shipment\Domain\Exception\ShipmentNotFoundException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Webmozart\Assert\InvalidArgumentException;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'exceptions' => [
            // Ordering
            OrderNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            OrderAlreadyCancelledException::class => ['log_level' => 'info', 'status_code' => 409],

            // Shipping
            ShipmentNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            InvalidShipmentTransitionException::class => ['log_level' => 'info', 'status_code' => 409],

            // Shared
            ApplicationExceptionInterface::class => ['log_level' => 'error', 'status_code' => 500],

            // WARNING: Must be the last entries. (Order matters: first match wins)
            InvalidArgumentException::class => ['log_level' => 'info', 'status_code' => 422],
            DomainException::class => ['log_level' => 'info', 'status_code' => 422],
        ],
    ]);
};
