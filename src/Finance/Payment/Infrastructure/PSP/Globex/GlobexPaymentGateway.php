<?php

declare(strict_types=1);

namespace Finance\Payment\Infrastructure\PSP\Globex;

use Finance\Payment\Application\PSP\Exception\PaymentFatalFailureException;
use Finance\Payment\Application\PSP\Exception\PaymentGatewayException;
use Finance\Payment\Application\PSP\PaymentGatewayInterface;
use Finance\Payment\Application\PSP\PaymentGatewayStatus;
use Finance\Payment\Application\PSP\PaymentLine;
use Finance\Payment\Application\PSP\PaymentSession;
use Webmozart\Assert\Assert;

final readonly class GlobexPaymentGateway implements PaymentGatewayInterface
{
    private const string SESSIONS_PATH = '/checkout/sessions';

    public function __construct(private GlobexClient $globexClient)
    {
    }

    /**
     * @param list<PaymentLine> $lines
     *
     * @throws PaymentGatewayException
     */
    public function requestPayment(string $paymentId, string $checkoutSessionId, array $lines, string $successUrl, string $cancelUrl, \DateTimeImmutable $expiresAt): PaymentSession
    {
        Assert::notEmpty($lines, 'A checkout session needs at least one line, none given.');

        $response = $this->globexClient->post(self::SESSIONS_PATH, [
            'client_reference_id' => $checkoutSessionId,
            'mode' => 'payment',
            'ui_mode' => 'hosted_page',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'expires_at' => $expiresAt->getTimestamp(),
            'line_items' => array_map(
                static fn (PaymentLine $line): array => [
                    'price_data' => [
                        'currency' => strtolower($line->unitPrice->currency->value),
                        'unit_amount' => $line->unitPrice->cents,
                        'product_data' => [
                            'name' => $line->label->value,
                        ],
                    ],
                    'quantity' => $line->quantity->value,
                ],
                $lines,
            ),
            'metadata' => [
                'payment_id' => $paymentId,
                'checkout_session_id' => $checkoutSessionId,
            ],
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
