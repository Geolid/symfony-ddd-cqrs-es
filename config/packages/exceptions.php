<?php

declare(strict_types=1);

use Fulfilment\Shipment\Domain\Exception\InvalidShipmentTransitionException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Sales\Customer\Domain\Exception\CustomerAlreadyErasedException;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Webmozart\Assert\InvalidArgumentException;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'exceptions' => [
            // Sales
            CustomerNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            CustomerAlreadyErasedException::class => ['log_level' => 'info', 'status_code' => 409],
            OrderNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            OrderAlreadyCancelledException::class => ['log_level' => 'info', 'status_code' => 409],

            // Fulfilment
            ShipmentNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            InvalidShipmentTransitionException::class => ['log_level' => 'info', 'status_code' => 409],

            // Shared
            UniqueValueAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],
            ApplicationExceptionInterface::class => ['log_level' => 'error', 'status_code' => 500],

            // WARNING: Must be the last entries. (Order matters: first match wins)
            InvalidArgumentException::class => ['log_level' => 'info', 'status_code' => 422],
            DomainException::class => ['log_level' => 'info', 'status_code' => 422],
        ],
    ]);
};
