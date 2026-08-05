<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Application\Query\GetOrderSummaryLines;

use Sales\OrderSummary\Application\Finder\OrderSummaryLine\OrderSummaryLineResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<list<OrderSummaryLineResult>>
 */
final readonly class GetOrderSummaryLines implements QueryInterface
{
    public function __construct(public string $orderId)
    {
    }
}
