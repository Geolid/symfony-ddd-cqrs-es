<?php

declare(strict_types=1);

use Catalog\Product\Application\Exception\ProductLabelAlreadyTakenException;
use Fulfilment\Shipment\Application\Exception\TrackingReferenceAlreadyTakenException;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Iam\Identity\Application\Exception\LabelAlreadyTakenException;
use Iam\Identity\Application\Exception\LoginAlreadyTakenException;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Iam\Identity\Domain\Exception\IdentityNotActiveException;
use Sales\Customer\Application\Exception\CustomerEmailAlreadyRegisteredException;
use Sales\Order\Application\Exception\BuyerAddressesNotCompletedException;
use Sales\Order\Application\Exception\BuyerNotRegisteredException;
use Sales\Order\Application\Exception\OrderPaymentAlreadyCapturedException;
use Sales\Order\Application\Exception\OrderPaymentAlreadyRequestedException;
use Sales\Order\Application\Exception\OutdatedOrderException;
use Sales\Order\Application\Exception\PaymentReferenceAlreadyTakenException;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Exception\OrderBelongsToAnotherCustomerException;
use Sales\Order\Domain\Exception\OrderWithoutLineException;
use Sales\OrderSummary\Application\Exception\OrderSummaryResultNotFoundException;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Exception\ResultNotFoundException;
use Shared\Domain\Exception\AggregateNotFoundException;
use Shared\Domain\Exception\UniqueValueAlreadyTakenException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Webmozart\Assert\InvalidArgumentException;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'exceptions' => [
            // Catalog
            ProductLabelAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],

            // Fulfilment
            ShipmentInvalidTransitionException::class => ['log_level' => 'info', 'status_code' => 409],
            TrackingReferenceAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],

            // Iam
            IdentityNotActiveException::class => ['log_level' => 'info', 'status_code' => 409],
            IdentityAlreadyErasedException::class => ['log_level' => 'info', 'status_code' => 409],
            LabelAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],
            LoginAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],

            // Sales
            CustomerEmailAlreadyRegisteredException::class => ['log_level' => 'info', 'status_code' => 409],
            BuyerNotRegisteredException::class => ['log_level' => 'info', 'status_code' => 422],
            BuyerAddressesNotCompletedException::class => ['log_level' => 'info', 'status_code' => 422],
            OutdatedOrderException::class => ['log_level' => 'info', 'status_code' => 422],
            OrderBelongsToAnotherCustomerException::class => ['log_level' => 'info', 'status_code' => 403],
            OrderAlreadyCancelledException::class => ['log_level' => 'info', 'status_code' => 409],
            OrderPaymentAlreadyRequestedException::class => ['log_level' => 'info', 'status_code' => 409],
            OrderPaymentAlreadyCapturedException::class => ['log_level' => 'info', 'status_code' => 409],
            PaymentReferenceAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],
            OrderWithoutLineException::class => ['log_level' => 'info', 'status_code' => 422],
            OrderSummaryResultNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],

            // Shared
            AggregateNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            ResultNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            UniqueValueAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],
            ApplicationExceptionInterface::class => ['log_level' => 'error', 'status_code' => 500],

            // WARNING: Must be the last entries. (Order matters: first match wins)
            InvalidArgumentException::class => ['log_level' => 'info', 'status_code' => 422],
            DomainException::class => ['log_level' => 'info', 'status_code' => 422],
        ],
    ]);
};
