<?php

declare(strict_types=1);

use Catalog\Listing\Application\Uniqueness\Exception\ProductLabelAlreadyInUseException;
use Finance\Payment\Application\Checkout\Exception\PaymentRequestInProgressException;
use Finance\Payment\Application\Command\RequestPayment\Exception\PaymentAlreadyClaimedException;
use Finance\Payment\Application\PSP\Exception\PaymentGatewayException;
use Finance\Payment\Application\PSP\Exception\PaymentTransientFailureException;
use Finance\Payment\Application\Uniqueness\Exception\PaymentReferenceAlreadyInUseException;
use Fulfilment\Shipping\Application\Carrier\Exception\CarrierGatewayException;
use Fulfilment\Shipping\Application\Carrier\Exception\CarrierTransientFailureException;
use Fulfilment\Shipping\Application\Manifest\Exception\ManifestDeniedException;
use Fulfilment\Shipping\Application\Uniqueness\Exception\ShipmentTrackingNumberAlreadyInUseException;
use Fulfilment\Shipping\Domain\Exception\ShipmentAlreadyTrackedException;
use Fulfilment\Shipping\Domain\Exception\ShipmentInvalidTransitionException;
use Iam\Authentication\Application\ApiKey\Exception\ApiKeyCredentialRevokedException;
use Iam\Authentication\Application\Credential\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Password\Exception\CompromisedPasswordException;
use Iam\Authentication\Application\Uniqueness\Exception\ApiKeyCredentialLabelAlreadyInUseException;
use Iam\Authentication\Application\Uniqueness\Exception\PasswordCredentialLoginAlreadyInUseException;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Sales\Buyer\Application\Uniqueness\Exception\BuyerEmailAlreadyInUseException;
use Sales\Ordering\Application\Checkout\Exception\BuyerAddressesNotCompletedException as CheckoutBuyerAddressesNotCompletedException;
use Sales\Ordering\Application\Checkout\Exception\BuyerErasureRequestedException as CheckoutBuyerErasureRequestedException;
use Sales\Ordering\Application\Checkout\Exception\BuyerNotRegisteredException as CheckoutBuyerNotRegisteredException;
use Sales\Ordering\Application\Checkout\Exception\CartOutdatedException;
use Sales\Ordering\Application\Command\ConfirmOrder\Exception\BuyerAddressesNotCompletedException;
use Sales\Ordering\Application\Command\ConfirmOrder\Exception\BuyerNotRegisteredException;
use Sales\Ordering\Domain\Order\Exception\OrderBelongsToAnotherBuyerException;
use Sales\Ordering\Domain\Order\Exception\OrderNotCancellableException;
use Sales\Ordering\Domain\Order\Exception\OrderWithoutLineException;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Finder\Exception\ResultNotFoundException;
use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;
use Shared\Domain\Exception\AggregateAlreadyExistsException;
use Shared\Domain\Exception\AggregateNotFoundException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Webmozart\Assert\InvalidArgumentException;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'exceptions' => [
            // Catalog
            ProductLabelAlreadyInUseException::class => ['log_level' => 'info', 'status_code' => 409],

            // Fulfilment
            ShipmentAlreadyTrackedException::class => ['log_level' => 'info', 'status_code' => 409],
            ShipmentInvalidTransitionException::class => ['log_level' => 'info', 'status_code' => 409],
            ShipmentTrackingNumberAlreadyInUseException::class => ['log_level' => 'info', 'status_code' => 409],
            ManifestDeniedException::class => ['log_level' => 'info', 'status_code' => 409],
            CarrierTransientFailureException::class => ['log_level' => 'error', 'status_code' => 503],
            CarrierGatewayException::class => ['log_level' => 'error', 'status_code' => 502],

            // Iam
            IdentityAlreadyErasedException::class => ['log_level' => 'info', 'status_code' => 409],
            IdentityNotAuthenticatableException::class => ['log_level' => 'info', 'status_code' => 409],
            ApiKeyCredentialRevokedException::class => ['log_level' => 'info', 'status_code' => 409],
            ApiKeyCredentialOwnedByAnotherIdentityException::class => ['log_level' => 'info', 'status_code' => 403],
            ApiKeyCredentialLabelAlreadyInUseException::class => ['log_level' => 'info', 'status_code' => 409],
            PasswordCredentialLoginAlreadyInUseException::class => ['log_level' => 'info', 'status_code' => 409],
            SamePasswordException::class => ['log_level' => 'info', 'status_code' => 422],
            WeakPasswordException::class => ['log_level' => 'info', 'status_code' => 422],
            CompromisedPasswordException::class => ['log_level' => 'info', 'status_code' => 422],

            // Sales
            BuyerEmailAlreadyInUseException::class => ['log_level' => 'info', 'status_code' => 409],
            CheckoutBuyerNotRegisteredException::class => ['log_level' => 'info', 'status_code' => 422],
            CheckoutBuyerAddressesNotCompletedException::class => ['log_level' => 'info', 'status_code' => 422],
            CheckoutBuyerErasureRequestedException::class => ['log_level' => 'info', 'status_code' => 422],
            CartOutdatedException::class => ['log_level' => 'info', 'status_code' => 422],
            BuyerNotRegisteredException::class => ['log_level' => 'info', 'status_code' => 422],
            BuyerAddressesNotCompletedException::class => ['log_level' => 'info', 'status_code' => 422],
            OrderBelongsToAnotherBuyerException::class => ['log_level' => 'info', 'status_code' => 403],
            OrderNotCancellableException::class => ['log_level' => 'info', 'status_code' => 409],
            OrderWithoutLineException::class => ['log_level' => 'info', 'status_code' => 422],

            // Finance
            PaymentRequestInProgressException::class => ['log_level' => 'info', 'status_code' => 503],
            PaymentReferenceAlreadyInUseException::class => ['log_level' => 'info', 'status_code' => 409],
            PaymentAlreadyClaimedException::class => ['log_level' => 'info', 'status_code' => 409],
            PaymentTransientFailureException::class => ['log_level' => 'error', 'status_code' => 503],
            PaymentGatewayException::class => ['log_level' => 'error', 'status_code' => 502],

            // Shared
            AggregateNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            AggregateAlreadyExistsException::class => ['log_level' => 'info', 'status_code' => 409],
            ResultNotFoundException::class => ['log_level' => 'debug', 'status_code' => 404],
            UniquenessViolatedException::class => ['log_level' => 'info', 'status_code' => 409],
            ApplicationExceptionInterface::class => ['log_level' => 'error', 'status_code' => 500],

            // WARNING: Must be the last entries. (Order matters: first match wins)
            InvalidArgumentException::class => ['log_level' => 'info', 'status_code' => 422],
            DomainException::class => ['log_level' => 'info', 'status_code' => 422],
        ],
    ]);
};
