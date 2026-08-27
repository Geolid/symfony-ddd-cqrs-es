<?php

declare(strict_types=1);

namespace Sales\Order\Application\Query\GetOrderPaymentByReference;

use Sales\Order\Application\Exception\OrderPaymentResultNotFoundException;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentResult;
use Shared\Application\Query\QueryHandler;

#[QueryHandler]
final readonly class GetOrderPaymentByReferenceHandler
{
    public function __construct(private OrderPaymentFinderInterface $orderPaymentFinder)
    {
    }

    /**
     * @throws OrderPaymentResultNotFoundException
     */
    public function __invoke(GetOrderPaymentByReference $query): OrderPaymentResult
    {
        return $this->orderPaymentFinder->ofReference($query->reference);
    }
}
