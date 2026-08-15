<?php

declare(strict_types=1);

namespace Sales\Order\Application\Query\GetOrderPaymentByReference;

use Sales\Order\Application\Finder\OrderPayment\OrderPaymentFinderInterface;
use Sales\Order\Application\Finder\OrderPayment\OrderPaymentResult;
use Shared\Application\Exception\ResultNotFoundException;
use Shared\Application\Query\AsQueryHandler;

#[AsQueryHandler]
final readonly class GetOrderPaymentByReferenceHandler
{
    public function __construct(private OrderPaymentFinderInterface $orderPaymentFinder)
    {
    }

    /**
     * @throws ResultNotFoundException
     */
    public function __invoke(GetOrderPaymentByReference $query): OrderPaymentResult
    {
        return $this->orderPaymentFinder->ofReference($query->reference);
    }
}
