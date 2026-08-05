<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Application\Finder\OrderSummaryLine;

use Shared\Application\Finder\CollectionFinderInterface;

/**
 * @extends CollectionFinderInterface<OrderSummaryLineResult>
 */
interface OrderSummaryLineFinderInterface extends CollectionFinderInterface
{
    public function withOrder(string $orderId): static;
}
