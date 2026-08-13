<?php

declare(strict_types=1);

namespace Sales\Order\Application\Payment;

interface PaymentGatewayInterface
{
    public function requestPayment(string $orderId, int $amountInCents, string $returnUrl): PaymentSession;
}
