<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Application\Finder\OrderSummary;

use Sales\OrderSummary\Application\Exception\OrderSummaryResultNotFoundException;
use Sales\OrderSummary\Application\Status\OrderSummaryStatus;
use Shared\Application\Finder\IterableFinderInterface;
use Shared\Application\Finder\PaginatableFinderInterface;

/**
 * @extends IterableFinderInterface<OrderSummaryResult>
 * @extends PaginatableFinderInterface<OrderSummaryResult>
 */
interface OrderSummaryFinderInterface extends IterableFinderInterface, PaginatableFinderInterface
{
    /**
     * @throws OrderSummaryResultNotFoundException
     */
    public function ofOrderId(string $orderId): OrderSummaryResult;

    public function byCustomer(string $customerId): static;

    public function byStatus(OrderSummaryStatus $status): static;

    public function sortedByPlacedAt(): static;
}
