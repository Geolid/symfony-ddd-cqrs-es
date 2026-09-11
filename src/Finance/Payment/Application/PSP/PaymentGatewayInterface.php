<?php

declare(strict_types=1);

namespace Finance\Payment\Application\PSP;

use Finance\Payment\Application\PSP\Exception\PaymentGatewayException;
use Finance\Payment\Application\Requesting\PaymentSession;
use Shared\Domain\ValueObject\PostalAddress;

interface PaymentGatewayInterface
{
    /**
     * @throws PaymentGatewayException
     */
    public function requestPayment(string $paymentId, string $checkoutSessionId, int $amountInCents, string $returnUrl, PostalAddress $billingAddress, \DateTimeImmutable $expiresAt): PaymentSession;

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
