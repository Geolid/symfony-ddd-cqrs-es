<?php

declare(strict_types=1);

use Catalog\Product\Application\Exception\ProductLabelAlreadyTakenException;
use Fulfilment\Shipment\Application\Exception\TrackingReferenceAlreadyTakenException;
use Fulfilment\Shipment\Domain\Exception\ShipmentAlreadyTrackedException;
use Fulfilment\Shipment\Domain\Exception\ShipmentInvalidTransitionException;
use Iam\Authentication\Application\Exception\ApiKeyCredentialRevokedException;
use Iam\Authentication\Application\Exception\CompromisedPasswordException;
use Iam\Authentication\Application\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Exception\LabelAlreadyTakenException;
use Iam\Authentication\Application\Exception\LoginAlreadyTakenException;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Sales\Customer\Application\Exception\CustomerEmailAlreadyRegisteredException;
use Sales\Order\Application\Exception\BuyerAddressesNotCompletedException;
use Sales\Order\Application\Exception\BuyerNotRegisteredException;
use Sales\Order\Application\Exception\OrderPaymentRequestInProgressException;
use Sales\Order\Application\Exception\OutdatedOrderException;
use Sales\Order\Application\Exception\PaymentReferenceAlreadyTakenException;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Exception\OrderBelongsToAnotherCustomerException;
use Sales\Order\Domain\Exception\OrderNotCancellableException;
use Sales\Order\Domain\Exception\OrderNotCompletableException;
use Sales\Order\Domain\Exception\OrderNotReturnableException;
use Sales\Order\Domain\Exception\OrderReturnWindowExpiredException;
use Sales\Order\Domain\Exception\OrderWithoutLineException;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Exception\ResultNotFoundException;
use Shared\Application\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\Exception\AggregateAlreadyExistsException;
use Shared\Domain\Exception\AggregateNotFoundException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Webmozart\Assert\InvalidArgumentException;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'exceptions' => [
            // Catalog
            ProductLabelAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],

            // Fulfilment
            ShipmentAlreadyTrackedException::class => ['log_level' => 'info', 'status_code' => 409],
            ShipmentInvalidTransitionException::class => ['log_level' => 'info', 'status_code' => 409],
            TrackingReferenceAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],

            // Iam
            IdentityAlreadyErasedException::class => ['log_level' => 'info', 'status_code' => 409],
            IdentityNotAuthenticatableException::class => ['log_level' => 'info', 'status_code' => 409],
            ApiKeyCredentialRevokedException::class => ['log_level' => 'info', 'status_code' => 409],
            ApiKeyCredentialOwnedByAnotherIdentityException::class => ['log_level' => 'info', 'status_code' => 403],
            LabelAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],
            LoginAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],
            SamePasswordException::class => ['log_level' => 'info', 'status_code' => 422],
            WeakPasswordException::class => ['log_level' => 'info', 'status_code' => 422],
            CompromisedPasswordException::class => ['log_level' => 'info', 'status_code' => 422],

            // Sales
            CustomerEmailAlreadyRegisteredException::class => ['log_level' => 'info', 'status_code' => 409],
            BuyerNotRegisteredException::class => ['log_level' => 'info', 'status_code' => 422],
            BuyerAddressesNotCompletedException::class => ['log_level' => 'info', 'status_code' => 422],
            OutdatedOrderException::class => ['log_level' => 'info', 'status_code' => 422],
            OrderBelongsToAnotherCustomerException::class => ['log_level' => 'info', 'status_code' => 403],
            OrderAlreadyCancelledException::class => ['log_level' => 'info', 'status_code' => 409],
            OrderNotCancellableException::class => ['log_level' => 'info', 'status_code' => 409],
            OrderNotCompletableException::class => ['log_level' => 'info', 'status_code' => 409],
            OrderNotReturnableException::class => ['log_level' => 'info', 'status_code' => 409],
            OrderReturnWindowExpiredException::class => ['log_level' => 'info', 'status_code' => 409],
            OrderPaymentRequestInProgressException::class => ['log_level' => 'info', 'status_code' => 409],
            PaymentReferenceAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],
            OrderWithoutLineException::class => ['log_level' => 'info', 'status_code' => 422],

            // Shared
            AggregateNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            AggregateAlreadyExistsException::class => ['log_level' => 'info', 'status_code' => 409],
            ResultNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            UniqueValueAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],
            ApplicationExceptionInterface::class => ['log_level' => 'error', 'status_code' => 500],

            // WARNING: Must be the last entries. (Order matters: first match wins)
            InvalidArgumentException::class => ['log_level' => 'info', 'status_code' => 422],
            DomainException::class => ['log_level' => 'info', 'status_code' => 422],
        ],
    ]);
};
