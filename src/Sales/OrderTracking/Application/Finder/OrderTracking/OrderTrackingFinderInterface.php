<?php

declare(strict_types=1);

namespace Sales\OrderTracking\Application\Finder\OrderTracking;

use Shared\Application\Finder\PaginatedFinderInterface;

/**
 * @extends PaginatedFinderInterface<OrderTrackingResult>
 */
interface OrderTrackingFinderInterface extends PaginatedFinderInterface
{
    public function ofOrder(string $orderId): ?OrderTrackingResult;

    public function withCustomer(string $customerId): static;

    public function withStatus(string $status): static;
}
