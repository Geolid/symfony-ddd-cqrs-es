<?php

declare(strict_types=1);

namespace Sales\Order\Application\Query\ListOrdersPastRetentionPeriod;

use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Finder\Order\OrderResult;
use Shared\Application\Query\AsQueryHandler;
use Shared\Application\Query\Result\StreamResult;

#[AsQueryHandler]
final readonly class ListOrdersPastRetentionPeriodHandler
{
    public function __construct(private OrderFinderInterface $orderFinder)
    {
    }

    /**
     * @return StreamResult<OrderResult>
     */
    public function __invoke(ListOrdersPastRetentionPeriod $query): StreamResult
    {
        return new StreamResult($this->orderFinder->placedBefore($query->cutoff));
    }
}
