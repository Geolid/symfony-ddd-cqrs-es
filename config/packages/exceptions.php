<?php

declare(strict_types=1);

use Catalog\Listing\Application\Command\PublishProduct\Exception\ProductLabelAlreadyInUseException;
use Finance\Payment\Application\Checkout\Exception\PaymentRequestInProgressException;
use Finance\Payment\Application\Command\RequestPayment\Exception\PaymentAlreadyClaimedException;
use Finance\Payment\Application\Command\RequestPayment\Exception\PaymentReferenceAlreadyInUseException;
use Finance\Payment\Application\PSP\Exception\PaymentGatewayException;
use Finance\Payment\Application\PSP\Exception\PaymentTransientFailureException;
use Fulfilment\Shipping\Application\Carrier\Exception\CarrierGatewayException;
use Fulfilment\Shipping\Application\Carrier\Exception\CarrierTransientFailureException;
use Fulfilment\Shipping\Application\Command\ManifestShipment\Exception\ShipmentTrackingNumberAlreadyInUseException;
use Fulfilment\Shipping\Application\Manifest\Exception\ManifestDeniedException;
use Fulfilment\Shipping\Domain\Exception\ShipmentAlreadyTrackedException;
use Fulfilment\Shipping\Domain\Exception\ShipmentInvalidTransitionException;
use Iam\Authentication\Application\ApiKey\Exception\ApiKeyCredentialRevokedException;
use Iam\Authentication\Application\Command\DefinePasswordCredential\Exception\PasswordCredentialLoginAlreadyInUseException;
use Iam\Authentication\Application\Command\IssueApiKeyCredential\Exception\ApiKeyCredentialLabelAlreadyInUseException;
use Iam\Authentication\Application\Credential\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Password\Exception\CompromisedPasswordException;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Sales\Ordering\Application\Command\ConfirmOrder\Exception\ShopperAddressesNotCompletedException;
use Sales\Ordering\Application\Command\ConfirmOrder\Exception\ShopperNotRegisteredException;
use Sales\Ordering\Domain\Order\Exception\OrderBelongsToAnotherShopperException;
use Sales\Ordering\Domain\Order\Exception\OrderNotCancellableException;
use Sales\Ordering\Domain\Order\Exception\OrderWithoutLineException;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Finder\Exception\ResultNotFoundException;
use Shared\Application\Uniqueness\Exception\UniquenessViolatedException;
use Shared\Domain\Exception\AggregateAlreadyExistsException;
use Shared\Domain\Exception\AggregateNotFoundException;
use Shopping\Checkout\Application\Cart\Exception\CartOutdatedException;
use Shopping\Checkout\Application\Cart\Exception\ShopperAddressesNotCompletedException as CheckoutShopperAddressesNotCompletedException;
use Shopping\Checkout\Application\Cart\Exception\ShopperErasureRequestedException as CheckoutShopperErasureRequestedException;
use Shopping\Checkout\Application\Cart\Exception\ShopperNotRegisteredException as CheckoutShopperNotRegisteredException;
use Shopping\Checkout\Application\Command\RegisterShopper\Exception\ShopperEmailAlreadyInUseException;
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

            // Shopping
            ShopperEmailAlreadyInUseException::class => ['log_level' => 'info', 'status_code' => 409],

            // Sales
            CheckoutShopperNotRegisteredException::class => ['log_level' => 'info', 'status_code' => 422],
            CheckoutShopperAddressesNotCompletedException::class => ['log_level' => 'info', 'status_code' => 422],
            CheckoutShopperErasureRequestedException::class => ['log_level' => 'info', 'status_code' => 422],
            CartOutdatedException::class => ['log_level' => 'info', 'status_code' => 422],
            ShopperNotRegisteredException::class => ['log_level' => 'info', 'status_code' => 422],
            ShopperAddressesNotCompletedException::class => ['log_level' => 'info', 'status_code' => 422],
            OrderBelongsToAnotherShopperException::class => ['log_level' => 'info', 'status_code' => 403],
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
