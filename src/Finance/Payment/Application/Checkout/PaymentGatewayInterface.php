<?php

declare(strict_types=1);

namespace Finance\Payment\Application\Checkout;

use Shared\Domain\ValueObject\PostalAddress;

interface PaymentGatewayInterface
{
    public function requestPayment(string $orderId, int $amountInCents, string $returnUrl, PostalAddress $billingAddress): PaymentSession;

    public function void(string $reference): void;

    public function refund(string $reference): void;

    public function checkStatus(string $reference): PaymentGatewayStatus;
}
