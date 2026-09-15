<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Requesting;

use Finance\Payment\Application\Command\RequestPayment\Exception\PaymentAlreadyClaimedException;
use Finance\Payment\Application\Command\RequestPayment\RequestPayment;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentUniqueKey;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Shared\Domain\ValueObject\PostalAddress;

final readonly class PaymentRequester implements PaymentRequesterInterface
{
    public function __construct(
        private UniquenessRegistryInterface $uniqueValues,
        private PaymentFinderInterface $paymentFinder,
        private PaymentGatewayInterface $paymentGateway,
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function requestFor(string $checkoutSessionId, int $amountInCents, string $currency, PostalAddress $billingAddress, string $successUrl, string $cancelUrl, \DateTimeImmutable $expiresAt): string
    {
        $checkoutSessionKey = UniqueKey::for(PaymentUniqueKey::CHECKOUT_SESSION);

        if ($this->uniqueValues->isClaimed($checkoutSessionKey, $checkoutSessionId)) {
            return $this->paymentFinder->ofCheckoutSession($checkoutSessionId)->checkoutUrl;
        }

        $paymentId = PaymentId::forCheckoutSession($checkoutSessionId);

        $session = $this->paymentGateway->requestPayment($paymentId->toString(), $checkoutSessionId, $amountInCents, $successUrl, $cancelUrl, $billingAddress, $expiresAt);

        try {
            $this->commandBus->dispatch(new RequestPayment(
                id: $paymentId->toString(),
                checkoutSessionId: $checkoutSessionId,
                amountInCents: $amountInCents,
                currency: $currency,
                reference: $session->reference,
                checkoutUrl: $session->checkoutUrl,
            ));
        } catch (PaymentAlreadyClaimedException) {
            return $this->paymentFinder->ofCheckoutSession($checkoutSessionId)->checkoutUrl;
        }

        return $session->checkoutUrl;
    }
}
