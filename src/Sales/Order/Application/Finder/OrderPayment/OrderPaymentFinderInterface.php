<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\OrderPayment;

interface OrderPaymentFinderInterface
{
    public function ofReference(string $reference): ?OrderPaymentResult;
}
