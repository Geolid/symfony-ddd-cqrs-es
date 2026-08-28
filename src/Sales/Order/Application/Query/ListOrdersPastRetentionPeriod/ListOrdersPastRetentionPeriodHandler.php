<?php

declare(strict_types=1);

namespace Sales\Order\Application\Query\ListOrdersPastRetentionPeriod;

use Psr\Clock\ClockInterface;
use Sales\Order\Application\Finder\Order\OrderFinderInterface;
use Sales\Order\Application\Finder\Order\OrderResult;
use Sales\Order\Domain\Specification\RetentionExpiredSpecification;
use Shared\Application\Query\QueryHandler;
use Shared\Application\Query\Result\StreamResult;

#[QueryHandler]
final readonly class ListOrdersPastRetentionPeriodHandler
{
    public function __construct(
        private OrderFinderInterface $orderFinder,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return StreamResult<OrderResult>
     */
    public function __invoke(ListOrdersPastRetentionPeriod $query): StreamResult
    {
        $cutoff = $this->clock->now()->modify(\sprintf('-%d days', RetentionExpiredSpecification::DAYS));

        return new StreamResult($this->orderFinder->closedBefore($cutoff));
    }
}
