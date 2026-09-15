<?php

declare(strict_types=1);

namespace Finance\Payment\Application\PSP;

use Finance\Payment\Application\PSP\Exception\PaymentGatewayException;

interface PaymentGatewayInterface
{
    /**
     * @param list<PaymentLine> $lines each line's unit price is tax-inclusive
     *
     * @throws PaymentGatewayException
     */
    public function requestPayment(string $paymentId, string $checkoutSessionId, array $lines, string $successUrl, string $cancelUrl, \DateTimeImmutable $expiresAt): PaymentSession;

    /**
     * @throws PaymentGatewayException
     */
    public function capture(string $reference): PaymentGatewayStatus;

    /**
     * @throws PaymentGatewayException
     */
    public function void(string $reference): PaymentGatewayStatus;

    /**
     * @throws PaymentGatewayException
     */
    public function checkStatus(string $reference): PaymentGatewayStatus;
}
