<?php

declare(strict_types=1);

use Fulfilment\Shipment\Application\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Sales\Customer\Application\Exception\AddressAlreadyRegisteredException;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Order\Application\Exception\BuyerNotRegisteredException;
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
            AddressAlreadyRegisteredException::class => ['log_level' => 'info', 'status_code' => 409],
            CustomerNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            BuyerNotRegisteredException::class => ['log_level' => 'info', 'status_code' => 422],
            OrderNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            OrderAlreadyCancelledException::class => ['log_level' => 'info', 'status_code' => 409],

            // Fulfilment
            ShipmentNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            ShipmentResultNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            ShipmentInvalidTransitionException::class => ['log_level' => 'info', 'status_code' => 409],

            // Shared
            UniqueValueAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],
            ApplicationExceptionInterface::class => ['log_level' => 'error', 'status_code' => 500],

            // WARNING: Must be the last entries. (Order matters: first match wins)
            InvalidArgumentException::class => ['log_level' => 'info', 'status_code' => 422],
            DomainException::class => ['log_level' => 'info', 'status_code' => 422],
        ],
    ]);
};
