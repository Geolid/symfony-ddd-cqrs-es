<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Requesting;

use Finance\Payment\Application\Command\RequestPayment\Exception\PaymentAlreadyClaimedException;
use Finance\Payment\Application\Command\RequestPayment\RequestPayment;
use Finance\Payment\Application\Finder\Payment\PaymentFinderInterface;
use Finance\Payment\Application\PaymentUniqueKey;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Application\PSP\PaymentLine;
use Finance\Payment\Domain\ValueObject\PaymentId;
use Shared\Application\Command\CommandBusInterface;
use Shared\Application\Exception\ApplicationExceptionInterface;
use Shared\Application\Uniqueness\UniqueKey;
use Shared\Application\Uniqueness\UniquenessRegistryInterface;
use Webmozart\Assert\Assert;

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
     * @param list<PaymentLine> $lines
     *
     * @throws ApplicationExceptionInterface
     * @throws \DomainException
     */
    public function requestFor(string $checkoutSessionId, array $lines, string $successUrl, string $cancelUrl, \DateTimeImmutable $expiresAt): string
    {
        Assert::notEmpty($lines, 'A checkout session needs at least one line, none given.');

        $checkoutSessionKey = UniqueKey::for(PaymentUniqueKey::CHECKOUT_SESSION);

        if ($this->uniqueValues->isClaimed($checkoutSessionKey, $checkoutSessionId)) {
            return $this->paymentFinder->ofCheckoutSession($checkoutSessionId)->hostedPageUrl;
        }

        $paymentId = PaymentId::forCheckoutSession($checkoutSessionId);

        $session = $this->paymentGateway->requestPayment($paymentId->toString(), $checkoutSessionId, $lines, $successUrl, $cancelUrl, $expiresAt);

        $amountInCents = array_sum(array_map(
            static fn (PaymentLine $line): int => $line->unitPrice->cents * $line->quantity->value,
            $lines,
        ));
        $currency = $lines[0]->unitPrice->currency->value;

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
