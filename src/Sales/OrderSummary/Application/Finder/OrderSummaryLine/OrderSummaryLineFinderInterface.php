<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Application\Finder\OrderSummaryLine;

use Shared\Application\Finder\IterableFinderInterface;

/**
 * @extends IterableFinderInterface<OrderSummaryLineResult>
 */
interface OrderSummaryLineFinderInterface extends IterableFinderInterface
{
    public function byOrder(string $orderId): static;
}
