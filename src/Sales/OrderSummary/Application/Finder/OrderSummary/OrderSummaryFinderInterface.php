<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Application\Finder\OrderSummary;

use Sales\OrderSummary\Application\Exception\OrderSummaryResultNotFoundException;
use Sales\OrderSummary\Application\OrderSummaryStatus;
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
    public function ofOrder(string $orderId): OrderSummaryResult;

    public function byBuyer(string $buyerId): static;

    public function byStatus(OrderSummaryStatus $status): static;

    public function sortedByPlacedAt(): static;
}
