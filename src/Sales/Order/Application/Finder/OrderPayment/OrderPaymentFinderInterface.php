<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\OrderPayment;

use Sales\Order\Application\Exception\OrderPaymentResultNotFoundException;
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

    public function byStatus(string ...$statuses): static;

    public function stalledBefore(string $cutoff): static;
}
