<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\OrderPayment;

use Sales\Order\Application\Exception\OrderPaymentResultNotFoundException;
use Shared\Application\Finder\FinderInterface;

interface OrderPaymentFinderInterface extends FinderInterface
{
    /**
     * @throws OrderPaymentResultNotFoundException
     */
    public function ofReference(string $reference): OrderPaymentResult;
}
