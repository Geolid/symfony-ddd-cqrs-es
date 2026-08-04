<?php

declare(strict_types=1);

namespace Sales\Order\Application\Query\GetOrderLines;

use Sales\Order\Application\Finder\OrderLine\OrderLineResult;
use Shared\Application\Query\QueryInterface;

/**
 * @implements QueryInterface<list<OrderLineResult>>
 */
final readonly class GetOrderLines implements QueryInterface
{
    public function __construct(public string $orderId)
    {
    }
}
