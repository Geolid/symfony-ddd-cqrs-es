<?php

declare(strict_types=1);

namespace Finance\Payment\Infrastructure\PSP\Globex;

use Finance\Payment\Application\PSP\Exception\PaymentFatalFailureException;
use Finance\Payment\Application\PSP\Exception\PaymentGatewayException;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Application\PSP\PaymentGatewayStatus;
use Finance\Payment\Application\Requesting\PaymentSession;
use Shared\Application\Mapper\PostalAddressMapper;
use Shared\Domain\ValueObject\PostalAddress;

final readonly class GlobexPaymentGateway implements PaymentGatewayInterface
{
    private const string CHARGES_PATH = '/charges';

    public function __construct(private GlobexClient $globexClient)
    {
    }

    /**
     * @throws PaymentGatewayException
     */
    public function requestPayment(string $paymentId, string $checkoutSessionId, int $amountInCents, string $returnUrl, PostalAddress $billingAddress, \DateTimeImmutable $expiresAt): PaymentSession
    {
        $response = $this->globexClient->post(self::CHARGES_PATH, [
            'merchantReference' => $checkoutSessionId,
            'amountInCents' => $amountInCents,
            'returnUrl' => $returnUrl,
            'billingAddress' => PostalAddressMapper::toArray($billingAddress),
            'expiresAt' => $expiresAt->format(\DateTimeInterface::ATOM),
        ], $paymentId);

        $chargeReference = $response['chargeReference'] ?? null;
        $checkoutUrl = $response['checkoutUrl'] ?? null;

        if (!\is_string($chargeReference) || '' === $chargeReference) {
            throw PaymentFatalFailureException::forReason('A charge response carries a non-empty "chargeReference".');
        }

        if (!\is_string($checkoutUrl) || '' === $checkoutUrl) {
            throw PaymentFatalFailureException::forReason('A charge response carries a non-empty "checkoutUrl".');
        }

        return new PaymentSession($chargeReference, $checkoutUrl);
    }

    /**
     * @throws PaymentGatewayException
     */
    public function capture(string $reference): PaymentGatewayStatus
    {
        return $this->parseStatus($this->globexClient->post(\sprintf('%s/%s/capture', self::CHARGES_PATH, $reference), []));
    }

    /**
     * @throws PaymentGatewayException
     */
    public function void(string $reference): PaymentGatewayStatus
    {
        return $this->parseStatus($this->globexClient->post(\sprintf('%s/%s/void', self::CHARGES_PATH, $reference), []));
    }

    /**
     * @throws PaymentGatewayException
     */
    public function checkStatus(string $reference): PaymentGatewayStatus
    {
        return $this->parseStatus($this->globexClient->get(\sprintf('%s/%s', self::CHARGES_PATH, $reference)));
    }

    /**
     * @param array<string, mixed> $response
     *
     * @throws PaymentGatewayException
     */
    private function parseStatus(array $response): PaymentGatewayStatus
    {
        $status = $response['status'] ?? null;

        if (!\is_string($status) || '' === $status) {
            throw PaymentFatalFailureException::forReason('A response carries a non-empty "status".');
        }

        try {
            return PaymentGatewayStatus::from($status);
        } catch (\ValueError) {
            throw PaymentFatalFailureException::forReason(\sprintf('A response carries a recognized "status", got "%s".', $status));
        }
    }
}
