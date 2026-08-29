<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Application\Query\ListOrderSummaryLines;

use Sales\OrderSummary\Application\Finder\OrderSummaryLine\OrderSummaryLineResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<list<OrderSummaryLineResult>>
 */
final readonly class ListOrderSummaryLines implements QueryInterface
{
    public function __construct(public string $orderId)
    {
    }
}
