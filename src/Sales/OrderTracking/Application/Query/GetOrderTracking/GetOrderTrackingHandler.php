<?php

declare(strict_types=1);

namespace Sales\OrderTracking\Application\Query\GetOrderTracking;

use Sales\OrderTracking\Application\Finder\OrderTracking\OrderTrackingFinderInterface;
use Sales\OrderTracking\Application\Finder\OrderTracking\OrderTrackingResult;
use Shared\Application\Query\AsQueryHandler;

#[AsQueryHandler]
final readonly class GetOrderTrackingHandler
{
    public function __construct(private OrderTrackingFinderInterface $orderTrackingFinder)
    {
    }

    public function __invoke(GetOrderTracking $query): ?OrderTrackingResult
    {
        return $this->orderTrackingFinder->ofOrder($query->orderId);
    }
}
