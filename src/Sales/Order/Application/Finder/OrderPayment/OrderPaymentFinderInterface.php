<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\OrderPayment;

use Sales\Order\Application\Exception\OrderPaymentResultNotFoundException;
use Sales\Order\Application\OrderPaymentStatus;
use Shared\Application\Finder\IterableFinderInterface;

/**
 * @extends IterableFinderInterface<OrderPaymentResult>
 */
interface OrderPaymentFinderInterface extends IterableFinderInterface
{
    /**
     * @throws OrderPaymentResultNotFoundException
     */
    public function ofReference(string $reference): OrderPaymentResult;

    public function byStatus(OrderPaymentStatus $status): static;

    public function stalledBefore(\DateTimeImmutable $cutoff): static;
}
