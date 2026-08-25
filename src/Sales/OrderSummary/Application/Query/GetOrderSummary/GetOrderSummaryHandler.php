<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Application\Query\GetOrderSummary;

use Sales\OrderSummary\Application\Exception\OrderSummaryResultNotFoundException;
use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryFinderInterface;
use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryResult;
use Shared\Application\Query\QueryUseCase;

#[QueryUseCase]
final readonly class GetOrderSummaryHandler
{
    public function __construct(private OrderSummaryFinderInterface $orderSummaryFinder)
    {
    }

    /**
     * @throws OrderSummaryResultNotFoundException
     */
    public function __invoke(GetOrderSummary $query): OrderSummaryResult
    {
        return $this->orderSummaryFinder->ofOrder($query->orderId);
    }
}
