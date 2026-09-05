<?php

declare(strict_types=1);

namespace Finance\Payment\Infrastructure\Checkout\Globex;

use Finance\Payment\Application\Checkout\PaymentGatewayInterface;
use Finance\Payment\Application\Checkout\PaymentGatewayStatus;
use Finance\Payment\Application\Checkout\PaymentSession;
use Shared\Domain\ValueObject\PostalAddress;

final readonly class GlobexPaymentGateway implements PaymentGatewayInterface
{
    private const string CHARGES_PATH = '/charges';

    public function __construct(private GlobexClient $globexClient)
    {
    }

    /**
     * @throws GlobexClientException
     */
    public function requestPayment(string $orderId, int $amountInCents, string $returnUrl, PostalAddress $billingAddress): PaymentSession
    {
        $response = $this->globexClient->post(self::CHARGES_PATH, [
            'clientReferenceId' => $orderId,
            'amountInCents' => $amountInCents,
            'returnUrl' => $returnUrl,
            'billingAddress' => $billingAddress->toArray(),
        ], $orderId);

        $chargeReference = $response['chargeReference'] ?? null;
        $checkoutUrl = $response['checkoutUrl'] ?? null;

        if (!\is_string($chargeReference) || '' === $chargeReference) {
            throw GlobexClientException::invalidResponse(self::CHARGES_PATH, 'A charge response carries a non-empty "chargeReference".');
        }

        if (!\is_string($checkoutUrl) || '' === $checkoutUrl) {
            throw GlobexClientException::invalidResponse(self::CHARGES_PATH, 'A charge response carries a non-empty "checkoutUrl".');
        }

        return new PaymentSession($chargeReference, $checkoutUrl);
    }

    /**
     * @throws GlobexClientException
     */
    public function capture(string $reference): PaymentGatewayStatus
    {
        $response = $this->globexClient->post('/capture', ['reference' => $reference]);

        $status = $response['status'] ?? null;

        if (!\is_string($status) || '' === $status) {
            throw GlobexClientException::invalidResponse('/capture', 'A capture response carries a non-empty "status".');
        }

        try {
            return PaymentGatewayStatus::from($status);
        } catch (\ValueError) {
            throw GlobexClientException::invalidResponse('/capture', \sprintf('A capture response carries a recognized "status", got "%s".', $status));
        }
    }

    /**
     * @throws GlobexClientException
     */
    public function void(string $reference): void
    {
        $this->globexClient->post('/void', ['reference' => $reference]);
    }

    /**
     * @throws GlobexClientException
     */
    public function refund(string $reference): void
    {
        $this->globexClient->post('/refund', ['reference' => $reference]);
    }

    /**
     * @throws GlobexClientException
     */
    public function checkStatus(string $reference): PaymentGatewayStatus
    {
        $response = $this->globexClient->get(self::CHARGES_PATH.'/'.$reference);

        $status = $response['status'] ?? null;

        if (!\is_string($status) || '' === $status) {
            throw GlobexClientException::invalidResponse(self::CHARGES_PATH, 'A status response carries a non-empty "status".');
        }

        try {
            return PaymentGatewayStatus::from($status);
        } catch (\ValueError) {
            throw GlobexClientException::invalidResponse(self::CHARGES_PATH, \sprintf('A status response carries a recognized "status", got "%s".', $status));
        }
    }
}
