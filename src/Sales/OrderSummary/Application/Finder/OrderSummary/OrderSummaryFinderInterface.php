<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Application\Finder\OrderSummary;

use Sales\OrderSummary\Application\Exception\OrderSummaryResultNotFoundException;
use Shared\Application\Finder\PaginatedCollectionFinderInterface;

/**
 * @extends PaginatedCollectionFinderInterface<OrderSummaryResult>
 */
interface OrderSummaryFinderInterface extends PaginatedCollectionFinderInterface
{
    /**
     * @throws OrderSummaryResultNotFoundException
     */
    public function ofOrder(string $orderId): OrderSummaryResult;

    public function withCustomer(string $customerId): static;

    public function withStatus(string $status): static;

    public function sortedByPlacedAt(): static;
}
