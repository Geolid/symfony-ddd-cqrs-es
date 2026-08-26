<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Application\Finder\OrderSummary;

use Sales\OrderSummary\Application\Exception\OrderSummaryResultNotFoundException;
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

    public function byCustomer(string $customerId): static;

    public function byStatus(string $status): static;

    public function sortedByPlacedAt(): static;
}
