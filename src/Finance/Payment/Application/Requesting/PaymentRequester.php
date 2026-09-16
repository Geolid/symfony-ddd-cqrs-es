<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Requesting;

use Finance\Payment\Application\Command\RequestPayment\Exception\PaymentAlreadyClaimedException;
use Finance\Payment\Application\Command\RequestPayment\RequestPayment;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentUniqueKey;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Application\PSP\PaymentLine;
use Finance\Payment\Application\Requesting\Exception\PaymentRequestAlreadyExpiredException;
use Finance\Payment\Application\Requesting\Exception\PaymentRequestCurrencyMismatchException;
use Finance\Payment\Application\Requesting\Exception\PaymentRequestInvalidUrlException;
use Finance\Payment\Application\Requesting\Exception\PaymentRequestWithoutLineException;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Psr\Clock\ClockInterface;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;

final readonly class PaymentRequester implements PaymentRequesterInterface
{
    public function __construct(
        private UniquenessRegistryInterface $uniqueness,
        private PaymentFinderInterface $paymentFinder,
        private PaymentGatewayInterface $paymentGateway,
        private CommandBusInterface $commandBus,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param list<PaymentLine> $lines
     *
     * @throws PaymentRequestWithoutLineException
     * @throws PaymentRequestCurrencyMismatchException
     * @throws PaymentRequestInvalidUrlException
     * @throws PaymentRequestAlreadyExpiredException
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function requestFor(string $checkoutSessionId, string $currency, array $lines, string $successUrl, string $cancelUrl, \DateTimeImmutable $expiresAt): string
    {
        if ([] === $lines) {
            throw PaymentRequestWithoutLineException::forCheckoutSession($checkoutSessionId);
        }

        foreach ($lines as $line) {
            if ($line->unitPrice->currency->value !== $currency) {
                throw PaymentRequestCurrencyMismatchException::forCheckoutSession($checkoutSessionId);
            }
        }

        if (false === filter_var($successUrl, \FILTER_VALIDATE_URL) || false === filter_var($cancelUrl, \FILTER_VALIDATE_URL)) {
            throw PaymentRequestInvalidUrlException::forCheckoutSession($checkoutSessionId);
        }

        if ($expiresAt <= $this->clock->now()) {
            throw PaymentRequestAlreadyExpiredException::forCheckoutSession($checkoutSessionId);
        }

        $checkoutSessionKey = UniqueKey::for(PaymentUniqueKey::CHECKOUT_SESSION);

        if ($this->uniqueness->isClaimed($checkoutSessionKey, $checkoutSessionId)) {
            return $this->paymentFinder->ofCheckoutSession($checkoutSessionId)->hostedPageUrl;
        }

        $paymentId = PaymentId::forCheckoutSession($checkoutSessionId);

        $session = $this->paymentGateway->requestPayment($paymentId->toString(), $checkoutSessionId, $lines, $successUrl, $cancelUrl, $expiresAt);

        $amountInCents = array_sum(array_map(
            static fn (PaymentLine $line): int => $line->unitPrice->times($line->quantity)->cents,
            $lines,
        ));

        try {
            $this->commandBus->dispatch(new RequestPayment(
                id: $paymentId->toString(),
                checkoutSessionId: $checkoutSessionId,
                amountInCents: $amountInCents,
                currency: $currency,
                reference: $session->reference,
                hostedPageUrl: $session->hostedPageUrl,
            ));
        } catch (PaymentAlreadyClaimedException) {
            return $this->paymentFinder->ofCheckoutSession($checkoutSessionId)->hostedPageUrl;
        }

        return $session->hostedPageUrl;
    }
}
