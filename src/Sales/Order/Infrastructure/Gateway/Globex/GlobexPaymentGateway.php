<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Gateway\Globex;

use Sales\Order\Application\Gateway\PaymentGatewayInterface;
use Sales\Order\Application\Gateway\PaymentSession;
use Shared\Infrastructure\Gateway\Globex\Exception\GlobexClientException;
use Shared\Infrastructure\Gateway\Globex\GlobexClient;

final readonly class GlobexPaymentGateway implements PaymentGatewayInterface
{
    private const string CHARGES_PATH = '/charges';

    public function __construct(private GlobexClient $globexClient)
    {
    }

    /**
     * @throws GlobexClientException
     */
    public function requestPayment(string $orderId, int $amountInCents, string $returnUrl): PaymentSession
    {
        $response = $this->globexClient->post(self::CHARGES_PATH, [
            'reference' => $orderId,
            'amountInCents' => $amountInCents,
            'returnUrl' => $returnUrl,
        ]);

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
}
