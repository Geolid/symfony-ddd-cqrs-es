<?php

declare(strict_types=1);

use AfterSales\Return\Application\Command\RequestWithdrawal\Exception\ActiveWithdrawalAlreadyExistsException;
use AfterSales\Return\Application\Command\RequestWithdrawal\Exception\WithdrawalRequestInProgressException;
use AfterSales\Return\Domain\Exception\CannotRequestWithdrawalForAnotherBuyerException;
use AfterSales\Return\Domain\Exception\WithdrawalNotReceivedException;
use AfterSales\Return\Domain\Exception\WithdrawalWindowExpiredException;
use Catalog\Listing\Application\Command\PublishProduct\Exception\ProductLabelAlreadyTakenException;
use Finance\Payment\Application\Checkout\Exception\PaymentRequestInProgressException;
use Finance\Payment\Application\Checkout\Exception\PlacedOrderAlreadyCancelledException;
use Finance\Payment\Application\Command\RequestPayment\Exception\PaymentReferenceAlreadyTakenException;
use Finance\Payment\Application\PSP\Exception\PaymentGatewayException;
use Finance\Payment\Application\PSP\Exception\PaymentTransientFailureException;
use Fulfilment\Shipping\Application\Carrier\Exception\CarrierGatewayException;
use Fulfilment\Shipping\Application\Carrier\Exception\CarrierTransientFailureException;
use Fulfilment\Shipping\Application\Command\ManifestShipment\Exception\ShipmentTrackingNumberAlreadyTakenException;
use Fulfilment\Shipping\Application\Manifest\Exception\ManifestDeniedException;
use Fulfilment\Shipping\Domain\Exception\ShipmentAlreadyTrackedException;
use Fulfilment\Shipping\Domain\Exception\ShipmentInvalidTransitionException;
use Iam\Authentication\Application\ApiKey\Exception\ApiKeyCredentialLabelAlreadyTakenException;
use Iam\Authentication\Application\ApiKey\Exception\ApiKeyCredentialRevokedException;
use Iam\Authentication\Application\Credential\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Password\Exception\CompromisedPasswordException;
use Iam\Authentication\Application\Password\Exception\PasswordCredentialLoginAlreadyTakenException;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Sales\Buyer\Application\Command\RegisterBuyer\Exception\BuyerEmailAlreadyTakenException;
use Sales\Order\Application\Command\PlaceOrder\Exception\BuyerAddressesNotCompletedException;
use Sales\Order\Application\Command\PlaceOrder\Exception\BuyerNotRegisteredException;
use Sales\Order\Application\Command\PlaceOrder\Exception\OutdatedOrderException;
use Sales\Order\Domain\Exception\OrderBelongsToAnotherBuyerException;
use Sales\Order\Domain\Exception\OrderNotCancellableException;
use Sales\Order\Domain\Exception\OrderWithoutLineException;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Finder\Exception\ResultNotFoundException;
use Shared\Application\Uniqueness\Exception\UniqueValueAlreadyTakenException;
use Shared\Domain\Exception\AggregateAlreadyExistsException;
use Shared\Domain\Exception\AggregateNotFoundException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Webmozart\Assert\InvalidArgumentException;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'exceptions' => [
            // AfterSales
            CannotRequestWithdrawalForAnotherBuyerException::class => ['log_level' => 'info', 'status_code' => 403],
            WithdrawalWindowExpiredException::class => ['log_level' => 'info', 'status_code' => 409],
            WithdrawalNotReceivedException::class => ['log_level' => 'info', 'status_code' => 409],
            ActiveWithdrawalAlreadyExistsException::class => ['log_level' => 'info', 'status_code' => 409],
            WithdrawalRequestInProgressException::class => ['log_level' => 'info', 'status_code' => 503],

            // Catalog
            ProductLabelAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],

            // Fulfilment
            ShipmentAlreadyTrackedException::class => ['log_level' => 'info', 'status_code' => 409],
            ShipmentInvalidTransitionException::class => ['log_level' => 'info', 'status_code' => 409],
            ShipmentTrackingNumberAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],
            ManifestDeniedException::class => ['log_level' => 'info', 'status_code' => 409],
            CarrierTransientFailureException::class => ['log_level' => 'error', 'status_code' => 503],
            CarrierGatewayException::class => ['log_level' => 'error', 'status_code' => 502],

            // Iam
            IdentityAlreadyErasedException::class => ['log_level' => 'info', 'status_code' => 409],
            IdentityNotAuthenticatableException::class => ['log_level' => 'info', 'status_code' => 409],
            ApiKeyCredentialRevokedException::class => ['log_level' => 'info', 'status_code' => 409],
            ApiKeyCredentialOwnedByAnotherIdentityException::class => ['log_level' => 'info', 'status_code' => 403],
            ApiKeyCredentialLabelAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],
            PasswordCredentialLoginAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],
            SamePasswordException::class => ['log_level' => 'info', 'status_code' => 422],
            WeakPasswordException::class => ['log_level' => 'info', 'status_code' => 422],
            CompromisedPasswordException::class => ['log_level' => 'info', 'status_code' => 422],

            // Sales
            BuyerEmailAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],
            BuyerNotRegisteredException::class => ['log_level' => 'info', 'status_code' => 422],
            BuyerAddressesNotCompletedException::class => ['log_level' => 'info', 'status_code' => 422],
            OutdatedOrderException::class => ['log_level' => 'info', 'status_code' => 422],
            OrderBelongsToAnotherBuyerException::class => ['log_level' => 'info', 'status_code' => 403],
            OrderNotCancellableException::class => ['log_level' => 'info', 'status_code' => 409],
            OrderWithoutLineException::class => ['log_level' => 'info', 'status_code' => 422],

            // Finance
            PaymentRequestInProgressException::class => ['log_level' => 'info', 'status_code' => 503],
            PaymentReferenceAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],
            PlacedOrderAlreadyCancelledException::class => ['log_level' => 'info', 'status_code' => 409],
            PaymentTransientFailureException::class => ['log_level' => 'error', 'status_code' => 503],
            PaymentGatewayException::class => ['log_level' => 'error', 'status_code' => 502],

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
