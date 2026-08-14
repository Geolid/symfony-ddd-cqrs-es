<?php

declare(strict_types=1);

namespace Sales\Order\Application\Query\ListOrdersWithExpiredBillingRetention;

use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Finder\Order\OrderResult;
use Shared\Application\Query\AsQueryHandler;
use Shared\Application\Query\Result\StreamResult;

#[AsQueryHandler]
final readonly class ListOrdersWithExpiredBillingRetentionHandler
{
    public function __construct(private OrderFinderInterface $orderFinder)
    {
    }

    /**
     * @return StreamResult<OrderResult>
     */
    public function __invoke(ListOrdersWithExpiredBillingRetention $query): StreamResult
    {
        return new StreamResult($this->orderFinder->placedBefore($query->cutoff));
    }
}
