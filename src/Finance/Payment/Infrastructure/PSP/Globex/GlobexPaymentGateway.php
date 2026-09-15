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
    private const string SESSIONS_PATH = '/checkout/sessions';

    public function __construct(private GlobexClient $globexClient)
    {
    }

    /**
     * @throws PaymentGatewayException
     */
    public function requestPayment(string $paymentId, string $checkoutSessionId, int $amountInCents, string $successUrl, string $cancelUrl, PostalAddress $billingAddress, \DateTimeImmutable $expiresAt): PaymentSession
    {
        $response = $this->globexClient->post(self::SESSIONS_PATH, [
            'client_reference_id' => $checkoutSessionId,
            'amountInCents' => $amountInCents,
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'billingAddress' => PostalAddressMapper::toArray($billingAddress),
            'expiresAt' => $expiresAt->format(\DateTimeInterface::ATOM),
        ], $paymentId);

        $id = $response['id'] ?? null;
        $url = $response['url'] ?? null;

        if (!\is_string($id) || '' === $id) {
            throw PaymentFatalFailureException::forReason('A checkout session response carries a non-empty "id".');
        }

        if (!\is_string($url) || '' === $url) {
            throw PaymentFatalFailureException::forReason('A checkout session response carries a non-empty "url".');
        }

        return new PaymentSession($id, $url);
    }

    /**
     * @throws PaymentGatewayException
     */
    public function capture(string $reference): PaymentGatewayStatus
    {
        return $this->parseStatus($this->globexClient->post(\sprintf('%s/%s/capture', self::SESSIONS_PATH, $reference), [], \sprintf('%s:capture', $reference)));
    }

    /**
     * @throws PaymentGatewayException
     */
    public function void(string $reference): PaymentGatewayStatus
    {
        return $this->parseStatus($this->globexClient->post(\sprintf('%s/%s/cancel', self::SESSIONS_PATH, $reference), [], \sprintf('%s:cancel', $reference)));
    }

    /**
     * @throws PaymentGatewayException
     */
    public function checkStatus(string $reference): PaymentGatewayStatus
    {
        return $this->parseStatus($this->globexClient->get(\sprintf('%s/%s', self::SESSIONS_PATH, $reference)));
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
