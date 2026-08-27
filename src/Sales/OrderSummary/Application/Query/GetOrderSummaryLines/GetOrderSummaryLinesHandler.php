<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Application\Query\GetOrderSummaryLines;

use Sales\OrderSummary\Application\Finder\OrderSummaryLine\OrderSummaryLineFinderInterface;
use Sales\OrderSummary\Application\Finder\OrderSummaryLine\OrderSummaryLineResult;
use Shared\Application\Query\QueryHandler;

#[QueryHandler]
final readonly class GetOrderSummaryLinesHandler
{
    public function __construct(private OrderSummaryLineFinderInterface $orderSummaryLineFinder)
    {
    }

    /**
     * @return list<OrderSummaryLineResult>
     */
    public function __invoke(GetOrderSummaryLines $query): array
    {
        /** @var list<OrderSummaryLineResult> */
        return iterator_to_array($this->orderSummaryLineFinder->byOrder($query->orderId));
    }
}
