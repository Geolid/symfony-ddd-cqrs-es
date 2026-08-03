<?php

declare(strict_types=1);

namespace Sales\Order\Application\Query\GetOrderPaymentByOrder;

use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentResult;
use Shared\Application\Query\AsQueryHandler;

#[AsQueryHandler]
final readonly class GetOrderPaymentByOrderHandler
{
    public function __construct(private OrderPaymentFinderInterface $orderPaymentFinder)
    {
    }

    public function __invoke(GetOrderPaymentByOrder $query): ?OrderPaymentResult
    {
        return $this->orderPaymentFinder->ofOrder($query->orderId);
    }
}
