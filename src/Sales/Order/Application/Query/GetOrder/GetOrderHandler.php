<?php

declare(strict_types=1);

namespace Sales\Order\Application\Query\GetOrder;

use Sales\Order\Application\Exception\OrderResultNotFoundException;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Finder\Order\OrderResult;
use Shared\Application\Query\AsQueryHandler;

#[AsQueryHandler]
final readonly class GetOrderHandler
{
    public function __construct(private OrderFinderInterface $orderFinder)
    {
    }

    /**
     * @throws OrderResultNotFoundException
     */
    public function __invoke(GetOrder $query): OrderResult
    {
        return $this->orderFinder->ofId($query->id) ?? throw OrderResultNotFoundException::forId($query->id);
    }
}
