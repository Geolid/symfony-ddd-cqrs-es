<?php

declare(strict_types=1);

namespace Ordering\Order\Application\Query\GetOrder;

use Ordering\Order\Application\Exception\OrderResultNotFoundException;
use Ordering\Order\Application\Finder\Order\OrderFinderInterface;
use Ordering\Order\Application\Finder\Order\OrderResult;
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
        foreach ($this->orderFinder as $order) {
            if ($order->id === $query->id) {
                return $order;
            }
        }

        throw OrderResultNotFoundException::forId($query->id);
    }
}
