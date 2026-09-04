<?php

declare(strict_types=1);

use AfterSales\Return\Domain\Exception\CannotRequestWithdrawalForAnotherBuyerException;
use AfterSales\Return\Domain\Exception\WithdrawalNotReceivedException;
use AfterSales\Return\Domain\Exception\WithdrawalWindowExpiredException;
use Catalog\Listing\Application\Exception\ProductLabelAlreadyTakenException;
use Finance\Payment\Application\Exception\PaymentReferenceAlreadyTakenException;
use Finance\Payment\Application\Exception\PaymentRequestInProgressException;
use Finance\Payment\Application\Exception\PlacedOrderAlreadyCancelledException;
use Fulfilment\Shipping\Application\Exception\TrackingNumberAlreadyTakenException;
use Fulfilment\Shipping\Domain\Exception\ShipmentAlreadyTrackedException;
use Fulfilment\Shipping\Domain\Exception\ShipmentInvalidTransitionException;
use Iam\Authentication\Application\Exception\ApiKeyCredentialRevokedException;
use Iam\Authentication\Application\Exception\CompromisedPasswordException;
use Iam\Authentication\Application\Exception\IdentityNotAuthenticatableException;
use Iam\Authentication\Application\Exception\LabelAlreadyTakenException;
use Iam\Authentication\Application\Exception\LoginAlreadyTakenException;
use Iam\Authentication\Domain\ApiKeyCredential\Exception\ApiKeyCredentialOwnedByAnotherIdentityException;
use Iam\Authentication\Domain\PasswordCredential\Exception\SamePasswordException;
use Iam\Authentication\Domain\PasswordCredential\Exception\WeakPasswordException;
use Iam\Identity\Domain\Exception\IdentityAlreadyErasedException;
use Sales\Buyer\Application\Exception\BuyerEmailAlreadyRegisteredException;
use Sales\Order\Application\Exception\BuyerAddressesNotCompletedException;
use Sales\Order\Application\Exception\BuyerNotRegisteredException;
use Sales\Order\Application\Exception\OutdatedOrderException;
use Sales\Order\Domain\Exception\OrderAlreadyCancelledException;
use Sales\Order\Domain\Exception\OrderBelongsToAnotherBuyerException;
use Sales\Order\Domain\Exception\OrderNotCancellableException;
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
            // AfterSales
            CannotRequestWithdrawalForAnotherBuyerException::class => ['log_level' => 'info', 'status_code' => 403],
            WithdrawalWindowExpiredException::class => ['log_level' => 'info', 'status_code' => 409],
            WithdrawalNotReceivedException::class => ['log_level' => 'info', 'status_code' => 409],

            // Catalog
            ProductLabelAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],

            // Fulfilment
            ShipmentAlreadyTrackedException::class => ['log_level' => 'info', 'status_code' => 409],
            ShipmentInvalidTransitionException::class => ['log_level' => 'info', 'status_code' => 409],
            TrackingNumberAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],

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
            BuyerEmailAlreadyRegisteredException::class => ['log_level' => 'info', 'status_code' => 409],
            BuyerNotRegisteredException::class => ['log_level' => 'info', 'status_code' => 422],
            BuyerAddressesNotCompletedException::class => ['log_level' => 'info', 'status_code' => 422],
            OutdatedOrderException::class => ['log_level' => 'info', 'status_code' => 422],
            OrderBelongsToAnotherBuyerException::class => ['log_level' => 'info', 'status_code' => 403],
            OrderAlreadyCancelledException::class => ['log_level' => 'info', 'status_code' => 409],
            OrderNotCancellableException::class => ['log_level' => 'info', 'status_code' => 409],
            OrderWithoutLineException::class => ['log_level' => 'info', 'status_code' => 422],

            // Finance
            PaymentRequestInProgressException::class => ['log_level' => 'info', 'status_code' => 409],
            PaymentReferenceAlreadyTakenException::class => ['log_level' => 'info', 'status_code' => 409],
            PlacedOrderAlreadyCancelledException::class => ['log_level' => 'info', 'status_code' => 409],

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
