<?php

declare(strict_types=1);

namespace Sales\OrderSummary\Application\Query\GetOrderSummary;

use Sales\OrderSummary\Application\Finder\OrderSummary\OrderSummaryResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<?OrderSummaryResult>
 */
final readonly class GetOrderSummary implements QueryInterface
{
    public function __construct(public string $orderId)
    {
    }
}
