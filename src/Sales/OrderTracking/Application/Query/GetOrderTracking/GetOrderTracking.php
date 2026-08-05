<?php

declare(strict_types=1);

namespace Sales\OrderTracking\Application\Query\GetOrderTracking;

use Sales\OrderTracking\Application\Finder\OrderTracking\OrderTrackingResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<?OrderTrackingResult>
 */
final readonly class GetOrderTracking implements QueryInterface
{
    public function __construct(public string $orderId)
    {
    }
}
