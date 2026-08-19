<?php

declare(strict_types=1);

namespace Sales\Order\Application\Payment;

use Shared\Domain\ValueObject\PostalAddress;

interface PaymentGatewayInterface
{
    public function requestPayment(string $orderId, int $amountInCents, string $returnUrl, PostalAddress $billingAddress): PaymentSession;

    public function void(string $reference): void;

    public function refund(string $reference): void;

    /**
     * @return string the gateway's own current status for this charge
     */
    public function checkStatus(string $reference): string;
}
