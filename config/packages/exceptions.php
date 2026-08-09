<?php

declare(strict_types=1);

use Catalog\Product\Application\Exception\ProductLabelAlreadyTakenException;
use Catalog\Product\Application\Exception\ProductResultNotFoundException;
use Catalog\Product\Domain\Exception\ProductAlreadyDelistedException;
use Catalog\Product\Domain\Exception\ProductNotFoundException;
use Fulfilment\Shipment\Application\Exception\ShipmentResultNotFoundException;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Fulfilment\Shipment\Domain\Exception\ShipmentNotFoundException;
use Iam\Access\Domain\Exception\GrantNotFoundException;
use Iam\Access\Domain\Exception\PermissionAlreadyRevokedException;
use Iam\Identity\Application\Exception\IdentityResultNotFoundException;
use Iam\Identity\Application\Exception\LoginAlreadyTakenException;
use Iam\Identity\Domain\Exception\ApiTokenCredentialAlreadyRevokedException;
use Iam\Identity\Domain\Exception\ApiTokenCredentialNotFoundException;
use Iam\Identity\Domain\Exception\IdentityAlreadySuspendedException;
use Iam\Identity\Domain\Exception\IdentityNotFoundException;
use Iam\Identity\Domain\Exception\IdentityNotSuspendedException;
use Iam\Identity\Domain\Exception\PasswordCredentialNotFoundException;
use Sales\Customer\Application\Exception\AddressAlreadyRegisteredException;
use Sales\Customer\Application\Exception\CustomerResultNotFoundException;
use Sales\Customer\Domain\Exception\CustomerNotFoundException;
use Sales\Order\Application\Exception\BuyerNotRegisteredException;
use Sales\Order\Application\Exception\OrderPaymentAlreadyCapturedException;
use Sales\Order\Application\Exception\OrderPaymentAlreadyRequestedException;
use Sales\Order\Application\Exception\OrderPaymentResultNotFoundException;
use Sales\Order\Application\Exception\OrderResultNotFoundException;
use Sales\Order\Application\Exception\ProductNotAvailableException;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Exception\OrderBelongsToAnotherCustomerException;
use Sales\Order\Domain\Exception\OrderNotFoundException;
use Sales\Order\Domain\Exception\OrderPaymentInvalidTransitionException;
use Sales\Order\Domain\Exception\OrderPaymentNotFoundException;
use Sales\Order\Domain\Exception\OrderWithoutLineException;
use Sales\OrderSummary\Application\Exception\OrderSummaryResultNotFoundException;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Webmozart\Assert\InvalidArgumentException;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'exceptions' => [
            // Catalog
            ProductNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            ProductAlreadyDelistedException::class => ['log_level' => 'info', 'status_code' => 409],
            ProductLabelAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],
            ProductResultNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],

            // Fulfilment
            ShipmentNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            ShipmentResultNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            ShipmentInvalidTransitionException::class => ['log_level' => 'info', 'status_code' => 409],

            // Iam
            GrantNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            PermissionAlreadyRevokedException::class => ['log_level' => 'info', 'status_code' => 409],
            ApiTokenCredentialNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            ApiTokenCredentialAlreadyRevokedException::class => ['log_level' => 'info', 'status_code' => 409],
            IdentityNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            IdentityAlreadySuspendedException::class => ['log_level' => 'info', 'status_code' => 409],
            IdentityNotSuspendedException::class => ['log_level' => 'info', 'status_code' => 409],
            IdentityResultNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            LoginAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],
            PasswordCredentialNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],

            // Sales
            AddressAlreadyRegisteredException::class => ['log_level' => 'info', 'status_code' => 409],
            CustomerNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            CustomerResultNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            BuyerNotRegisteredException::class => ['log_level' => 'info', 'status_code' => 422],
            ProductNotAvailableException::class => ['log_level' => 'info', 'status_code' => 422],
            OrderNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            OrderBelongsToAnotherCustomerException::class => ['log_level' => 'info', 'status_code' => 403],
            OrderAlreadyCancelledException::class => ['log_level' => 'info', 'status_code' => 409],
            OrderResultNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            OrderPaymentNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            OrderPaymentResultNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            OrderPaymentInvalidTransitionException::class => ['log_level' => 'info', 'status_code' => 409],
            OrderPaymentAlreadyRequestedException::class => ['log_level' => 'info', 'status_code' => 409],
            OrderPaymentAlreadyCapturedException::class => ['log_level' => 'info', 'status_code' => 409],
            OrderWithoutLineException::class => ['log_level' => 'info', 'status_code' => 422],
            OrderSummaryResultNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],

            // Shared
            UniqueValueAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],
            ApplicationExceptionInterface::class => ['log_level' => 'error', 'status_code' => 500],

            // WARNING: Must be the last entries. (Order matters: first match wins)
            InvalidArgumentException::class => ['log_level' => 'info', 'status_code' => 422],
            DomainException::class => ['log_level' => 'info', 'status_code' => 422],
        ],
    ]);
};
