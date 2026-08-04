<?php

declare(strict_types=1);

namespace Sales\Order\Application\Query\GetOrderLines;

use Sales\Order\Application\Finder\OrderLine\OrderLineFinderInterface;
use Sales\Order\Application\Finder\OrderLine\OrderLineResult;
use Shared\Application\Query\AsQueryHandler;

#[AsQueryHandler]
final readonly class GetOrderLinesHandler
{
    public function __construct(private OrderLineFinderInterface $orderLineFinder)
    {
    }

    /**
     * @return list<OrderLineResult>
     */
    public function __invoke(GetOrderLines $query): array
    {
        return $this->orderLineFinder->allForOrder($query->orderId);
    }
}
