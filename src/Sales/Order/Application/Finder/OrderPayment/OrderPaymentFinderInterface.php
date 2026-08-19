<?php

declare(strict_types=1);

namespace Sales\Order\Application\Finder\OrderPayment;

use Sales\Order\Application\Exception\OrderPaymentResultNotFoundException;
use Shared\Application\Finder\CollectionFinderInterface;

/**
 * @extends CollectionFinderInterface<OrderPaymentResult>
 */
interface OrderPaymentFinderInterface extends CollectionFinderInterface
{
    /**
     * @throws OrderPaymentResultNotFoundException
     */
    public function ofReference(string $reference): OrderPaymentResult;

    public function byStatus(string ...$statuses): static;

    public function requestedBefore(string $cutoff): static;
}
