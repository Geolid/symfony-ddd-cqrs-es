<?php

declare(strict_types=1);

namespace Sales\Order\Application\Gateway;

interface PaymentGatewayInterface
{
    public function requestPayment(string $orderId, int $amountInCents, int $itemCount, string $returnUrl): PaymentSession;
}
