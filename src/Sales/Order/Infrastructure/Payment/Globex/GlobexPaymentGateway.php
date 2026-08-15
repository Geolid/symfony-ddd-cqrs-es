<?php

declare(strict_types=1);

namespace Sales\Order\Infrastructure\Payment\Globex;

use Sales\Order\Application\Payment\PaymentGatewayInterface;
use Sales\Order\Application\Payment\PaymentSession;
use Sales\Order\Infrastructure\Payment\Globex\Exception\GlobexClientException;
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
            'reference' => $orderId,
            'amountInCents' => $amountInCents,
            'returnUrl' => $returnUrl,
            'billingAddress' => [
                'firstName' => $billingAddress->fullName->firstName,
                'lastName' => $billingAddress->fullName->lastName,
                'street' => $billingAddress->address->street,
                'postalCode' => $billingAddress->address->postalCode,
                'city' => $billingAddress->address->city,
            ],
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
}
