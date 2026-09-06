<?php

declare(strict_types=1);

namespace Finance\Payment\Application\PSP;

use Finance\Payment\Application\Checkout\PaymentSession;
use Finance\Payment\Application\PSP\Exception\PaymentGatewayException;
use Shared\Domain\ValueObject\PostalAddress;

interface PaymentGatewayInterface
{
    /**
     * @throws PaymentGatewayException
     */
    public function requestPayment(string $orderId, int $amountInCents, string $returnUrl, PostalAddress $billingAddress): PaymentSession;

    /**
     * @throws PaymentGatewayException
     */
    public function capture(string $reference): PaymentGatewayStatus;

    /**
     * @throws PaymentGatewayException
     */
    public function void(string $reference): void;

    /**
     * @throws PaymentGatewayException
     */
    public function refund(string $reference): void;

    /**
     * @throws PaymentGatewayException
     */
    public function checkStatus(string $reference): PaymentGatewayStatus;
}
