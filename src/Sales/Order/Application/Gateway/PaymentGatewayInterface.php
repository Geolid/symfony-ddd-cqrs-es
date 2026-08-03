<?php

declare(strict_types=1);

namespace Sales\Order\Application\Gateway;

interface PaymentGatewayInterface
{
    /**
     * @return string the payment provider's own reference
     */
    public function requestPayment(string $orderId, int $amountInCents): string;
}
