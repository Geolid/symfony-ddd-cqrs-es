<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\OrderPayment;

use Shared\Application\Finder\FinderInterface;

interface OrderPaymentFinderInterface extends FinderInterface
{
    public function ofReference(string $reference): ?OrderPaymentResult;

    public function ofOrder(string $orderId): ?OrderPaymentResult;
}
